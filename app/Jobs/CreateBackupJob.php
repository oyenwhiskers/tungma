<?php

namespace App\Jobs;

use App\Services\BackupProgressManager;
use App\Services\DatabaseBackupService;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 3600;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    public string $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        BackupProgressManager $progress,
        DatabaseBackupService $dbService,
        BackupService $backupService
    ): void {
        $progress->update($this->jobId, [
            'status' => 'in_progress',
            'stage' => 'Initializing backup...',
            'percentage' => 3,
        ]);

        $progress->log($this->jobId, "Starting backup job [{$this->jobId}].");

        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'temp_' . $this->jobId);
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $tempDumpPath = $tempDir . DIRECTORY_SEPARATOR . 'database.sql.gz';
        $timestamp = date('Y-m-d_His');
        $zipFilename = 'backup_' . $timestamp . '.zip';
        $backupDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipFilename;

        try {
            // -------------------------------------------------------------
            // Step 1: Database Dump
            // -------------------------------------------------------------
            $progress->update($this->jobId, [
                'stage' => 'Dumping database...',
                'percentage' => 5,
            ]);
            $progress->log($this->jobId, "Dumping MySQL database...");

            $dbService->dump($tempDumpPath, function ($message, $current, $total) use ($progress) {
                $progress->log($this->jobId, $message);
                if ($total > 0) {
                    $pct = 5 + (int) round(($current / $total) * 20); // 5% -> 25%
                    $progress->update($this->jobId, [
                        'percentage' => min($pct, 25),
                        'counter_text' => "Exporting table {$current} of {$total}",
                    ]);
                }
            });

            $dumpSizeHuman = $backupService->formatBytes(filesize($tempDumpPath));
            $progress->update($this->jobId, [
                'stage' => 'Database dumped (' . $dumpSizeHuman . ')',
                'percentage' => 25,
            ]);
            $progress->log($this->jobId, "Database dump complete: database.sql.gz ({$dumpSizeHuman}).");

            // -------------------------------------------------------------
            // Step 2: Discover and Collect Images
            // -------------------------------------------------------------
            $progress->update($this->jobId, [
                'stage' => 'Scanning receipt images...',
                'percentage' => 27,
            ]);
            $progress->log($this->jobId, "Scanning receipt image attachments...");

            $imagesToArchive = [];
            $mediaDisk = config('backup.model.disk', 'public');
            $mediaDiskRoot = Storage::disk($mediaDisk)->path('');

            $modelClass = config('backup.model.class');
            $imageColumns = config('backup.model.image_columns', ['media_attachment', 'payment_proof_attachment', 'image_path']);
            $chunkSize = config('backup.model.chunk_size', 250);

            // Chunked iteration over model
            if ($modelClass && class_exists($modelClass)) {
                $progress->log($this->jobId, "Iterating over model [{$modelClass}] in chunks of {$chunkSize}...");

                $modelClass::query()->chunk($chunkSize, function ($records) use (&$imagesToArchive, $imageColumns, $mediaDiskRoot) {
                    foreach ($records as $record) {
                        foreach ($imageColumns as $col) {
                            if (!empty($record->{$col})) {
                                $rel = (string) $record->{$col};
                                // Normalize path
                                $normalizedRel = ltrim(str_replace(['\\', '//'], '/', $rel), '/');
                                $abs = $mediaDiskRoot . str_replace('/', DIRECTORY_SEPARATOR, $normalizedRel);

                                if (file_exists($abs) && is_file($abs)) {
                                    $imagesToArchive[$normalizedRel] = $abs;
                                }
                            }
                        }
                    }
                });
            }

            // Also include all files in public storage if configured (prevents missed uploads)
            if (config('backup.model.include_all_public_files', true) && file_exists($mediaDiskRoot)) {
                $dirIterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($mediaDiskRoot, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($dirIterator as $item) {
                    if ($item->isFile()) {
                        $abs = $item->getRealPath();
                        // Ignore gitignore or root dotfiles
                        if ($item->getFilename() === '.gitignore') {
                            continue;
                        }
                        $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($mediaDiskRoot))), '/');
                        if (!empty($rel)) {
                            $imagesToArchive[$rel] = $abs;
                        }
                    }
                }
            }

            $totalImages = count($imagesToArchive);
            $progress->log($this->jobId, "Found {$totalImages} images to compress into archive.");

            // -------------------------------------------------------------
            // Step 3: Create ZIP Archive
            // -------------------------------------------------------------
            $progress->update($this->jobId, [
                'stage' => 'Creating backup ZIP archive...',
                'percentage' => 30,
                'counter_text' => "0 / {$totalImages} images compressed",
            ]);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Failed to create ZIP file at {$zipPath}");
            }

            // Add database.sql.gz at the root of the ZIP
            $zip->addFile($tempDumpPath, 'database.sql.gz');

            $processed = 0;
            $batchCounter = 0;

            foreach ($imagesToArchive as $relPath => $absPath) {
                // Store inside images/ folder in the zip, preserving relative path
                $zipEntryName = 'images/' . $relPath;
                $zip->addFile($absPath, $zipEntryName);

                $processed++;
                $batchCounter++;

                // Update live progress every 50 images or on the last one
                if ($processed % 50 === 0 || $processed === $totalImages) {
                    $percent = 30 + (int) round(($processed / max($totalImages, 1)) * 65); // 30% -> 95%
                    $progress->update($this->jobId, [
                        'stage' => 'Compressing images...',
                        'percentage' => min($percent, 95),
                        'counter_text' => number_format($processed) . ' / ' . number_format($totalImages) . ' images compressed',
                        'current_file' => $relPath,
                    ]);
                }

                // Log every 500 images and reopen zip to flush open file handles (avoid OS file descriptor limits)
                if ($batchCounter >= 500) {
                    $progress->log($this->jobId, "Compressed " . number_format($processed) . " / " . number_format($totalImages) . " images...");
                    $zip->close();
                    $zip->open($zipPath);
                    $batchCounter = 0;
                }
            }

            $progress->log($this->jobId, "Finalizing ZIP archive...");
            $zip->close();

            // -------------------------------------------------------------
            // Step 4: Cleanup & Complete
            // -------------------------------------------------------------
            @unlink($tempDumpPath);
            @rmdir($tempDir);

            $finalSize = filesize($zipPath);
            $finalSizeHuman = $backupService->formatBytes($finalSize);

            // Retention cleanup
            $retentionCount = config('backup.retention_count', 15);
            $backupService->cleanOldBackups($retentionCount);

            $result = [
                'filename' => $zipFilename,
                'size' => $finalSize,
                'size_human' => $finalSizeHuman,
                'total_images' => $totalImages,
                'date' => date('Y-m-d H:i:s'),
                'download_url' => route('backup.download', ['filename' => $zipFilename]),
            ];

            $progress->complete($this->jobId, $result);
            $progress->log($this->jobId, "Backup complete! Archive: {$zipFilename} ({$finalSizeHuman}).");

        } catch (\Throwable $e) {
            Log::error("Backup job failed [{$this->jobId}]: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Cleanup temp directory
            if (file_exists($tempDumpPath)) {
                @unlink($tempDumpPath);
            }
            if (file_exists($tempDir)) {
                @rmdir($tempDir);
            }
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }

            $progress->fail($this->jobId, $e->getMessage());
            throw $e;
        }
    }
}
