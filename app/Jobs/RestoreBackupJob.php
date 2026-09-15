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
use ZipArchive;

class RestoreBackupJob implements ShouldQueue
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
    public string $zipFilePath;
    public bool $isUploadedTemp;

    /**
     * Create a new job instance.
     */
    public function __construct(string $jobId, string $zipFilePath, bool $isUploadedTemp = false)
    {
        $this->jobId = $jobId;
        $this->zipFilePath = $zipFilePath;
        $this->isUploadedTemp = $isUploadedTemp;
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
            'stage' => 'Validating backup archive...',
            'percentage' => 5,
        ]);

        $progress->log($this->jobId, "Starting restore job [{$this->jobId}] from " . basename($this->zipFilePath) . ".");

        if (!file_exists($this->zipFilePath)) {
            $err = "Backup archive not found: {$this->zipFilePath}";
            $progress->fail($this->jobId, $err);
            throw new \Exception($err);
        }

        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'temp_restore_' . $this->jobId);
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($this->zipFilePath) !== true) {
            $err = "Failed to open ZIP archive: " . basename($this->zipFilePath);
            $progress->fail($this->jobId, $err);
            throw new \Exception($err);
        }

        try {
            // -------------------------------------------------------------
            // Step 1: Inspect ZIP Contents
            // -------------------------------------------------------------
            $numFiles = $zip->numFiles;
            $dbEntryName = null;
            $imageEntries = [];

            for ($i = 0; $i < $numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];

                // Check for database dump
                if (in_array(basename($name), ['database.sql.gz', 'database.sql', 'dump.sql.gz', 'dump.sql'], true)) {
                    $dbEntryName = $name;
                }

                // Check for images
                if (str_starts_with($name, 'images/') || str_starts_with($name, 'public/')) {
                    // Ignore directory entries
                    if (!str_ends_with($name, '/')) {
                        $imageEntries[] = $name;
                    }
                }
            }

            if (!$dbEntryName) {
                throw new \Exception("The archive does not contain a valid database dump (database.sql.gz).");
            }

            $totalImages = count($imageEntries);
            $progress->log($this->jobId, "Found database dump [{$dbEntryName}] and {$totalImages} image attachments in archive.");

            // -------------------------------------------------------------
            // Step 2: Extract and Restore Database (Full Overwrite)
            // -------------------------------------------------------------
            $progress->update($this->jobId, [
                'stage' => 'Extracting database dump...',
                'percentage' => 10,
            ]);
            $progress->log($this->jobId, "Extracting {$dbEntryName}...");

            $extractedDumpPath = $tempDir . DIRECTORY_SEPARATOR . basename($dbEntryName);
            $stream = $zip->getStream($dbEntryName);
            if (!$stream) {
                throw new \Exception("Could not read {$dbEntryName} from ZIP archive.");
            }

            $out = fopen($extractedDumpPath, 'wb');
            while (!feof($stream)) {
                fwrite($out, fread($stream, 65536));
            }
            fclose($stream);
            fclose($out);

            $progress->update($this->jobId, [
                'stage' => 'Restoring database (overwriting tables)...',
                'percentage' => 15,
            ]);
            $progress->log($this->jobId, "Restoring database tables (full replacement)...");

            $dbService->restore($extractedDumpPath, function ($message, $current, $total) use ($progress) {
                $progress->log($this->jobId, $message);
                if ($total > 0) {
                    $pct = 15 + (int) round(($current / $total) * 35); // 15% -> 50%
                    $progress->update($this->jobId, [
                        'percentage' => min($pct, 50),
                        'counter_text' => "Importing database queries...",
                    ]);
                }
            });

            @unlink($extractedDumpPath);
            $progress->update($this->jobId, [
                'stage' => 'Database successfully restored',
                'percentage' => 50,
            ]);
            $progress->log($this->jobId, "Database restoration complete. All tables replaced.");

            // -------------------------------------------------------------
            // Step 3: Extract Images into storage/app/public
            // -------------------------------------------------------------
            $mediaDisk = config('backup.model.disk', 'public');
            $mediaDiskRoot = Storage::disk($mediaDisk)->path('');

            if (!file_exists($mediaDiskRoot)) {
                @mkdir($mediaDiskRoot, 0755, true);
            }

            $progress->update($this->jobId, [
                'stage' => 'Restoring images to public storage...',
                'percentage' => 52,
                'counter_text' => "0 / {$totalImages} images restored",
            ]);
            $progress->log($this->jobId, "Extracting {$totalImages} images into storage/app/public...");

            $restoredCount = 0;
            foreach ($imageEntries as $entryName) {
                // Strip 'images/' or 'public/' prefix
                $relativeDest = $entryName;
                if (str_starts_with($relativeDest, 'images/')) {
                    $relativeDest = substr($relativeDest, strlen('images/'));
                } elseif (str_starts_with($relativeDest, 'public/')) {
                    $relativeDest = substr($relativeDest, strlen('public/'));
                }

                $relativeDest = ltrim(str_replace(['\\', '//'], '/', $relativeDest), '/');
                $targetPath = $mediaDiskRoot . str_replace('/', DIRECTORY_SEPARATOR, $relativeDest);

                $targetDir = dirname($targetPath);
                if (!file_exists($targetDir)) {
                    @mkdir($targetDir, 0755, true);
                }

                // Extract single file
                $fileStream = $zip->getStream($entryName);
                if ($fileStream) {
                    $targetFile = fopen($targetPath, 'wb');
                    while (!feof($fileStream)) {
                        fwrite($targetFile, fread($fileStream, 65536));
                    }
                    fclose($fileStream);
                    fclose($targetFile);
                    $restoredCount++;
                }

                if ($restoredCount % 50 === 0 || $restoredCount === $totalImages) {
                    $pct = 52 + (int) round(($restoredCount / max($totalImages, 1)) * 45); // 52% -> 97%
                    $progress->update($this->jobId, [
                        'stage' => 'Extracting images...',
                        'percentage' => min($pct, 97),
                        'counter_text' => number_format($restoredCount) . ' / ' . number_format($totalImages) . ' images restored',
                        'current_file' => $relativeDest,
                    ]);
                }

                if ($restoredCount % 500 === 0) {
                    $progress->log($this->jobId, "Restored " . number_format($restoredCount) . " / " . number_format($totalImages) . " images...");
                }
            }

            $zip->close();

            // -------------------------------------------------------------
            // Step 4: Cleanup & Complete
            // -------------------------------------------------------------
            if (file_exists($tempDir)) {
                @rmdir($tempDir);
            }

            // If user uploaded this zip as a temp restore file, delete the temp upload
            if ($this->isUploadedTemp && file_exists($this->zipFilePath)) {
                @unlink($this->zipFilePath);
            }

            $result = [
                'restored_images' => $restoredCount,
                'database_restored' => true,
                'restored_at' => date('Y-m-d H:i:s'),
                'source_archive' => basename($this->zipFilePath),
            ];

            $progress->complete($this->jobId, $result);
            $progress->log($this->jobId, "System restore completed successfully! ({$restoredCount} images restored, database overwritten).");

        } catch (\Throwable $e) {
            $zip->close();
            Log::error("Restore job failed [{$this->jobId}]: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if (file_exists($tempDir)) {
                $files = glob($tempDir . DIRECTORY_SEPARATOR . '*');
                foreach ($files as $f) {
                    @unlink($f);
                }
                @rmdir($tempDir);
            }

            if ($this->isUploadedTemp && file_exists($this->zipFilePath)) {
                @unlink($this->zipFilePath);
            }

            $progress->fail($this->jobId, $e->getMessage());
            throw $e;
        }
    }
}
