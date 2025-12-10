<?php

namespace App\Console\Commands;

use App\Http\Controllers\BackupController;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Optional: Artisan command for automated backups
 *
 * Usage:
 *   php artisan backup:data        # Backup data only
 *   php artisan backup:data --media # Backup data + media
 *   php artisan backup:data --all   # Backup everything
 *
 * To schedule:
 *   Add to app/Console/Kernel.php:
 *   $schedule->command('backup:data --all')->daily();
 */
class BackupData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:data
                            {--media : Also backup media files}
                            {--all : Backup both data and media}
                            {--clean : Clean old backups after backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create backup of bills data and/or media files';

    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting backup process...');

        $backupData = !$this->option('media');
        $backupMedia = $this->option('media') || $this->option('all');

        if ($this->option('all')) {
            $backupData = true;
            $backupMedia = true;
        }

        // Ensure backup directory exists
        $this->ensureBackupDirectory();

        // Backup data
        if ($backupData) {
            $this->info('📦 Backing up bills data...');
            try {
                $this->backupBillsData();
                $this->info('✅ Data backup completed successfully!');
            } catch (\Exception $e) {
                $this->error('❌ Data backup failed: ' . $e->getMessage());
                Log::error('Scheduled data backup failed: ' . $e->getMessage());
            }
        }

        // Backup media
        if ($backupMedia) {
            $this->info('📦 Backing up media files...');
            try {
                $this->backupMediaFiles();
                $this->info('✅ Media backup completed successfully!');
            } catch (\Exception $e) {
                $this->error('❌ Media backup failed: ' . $e->getMessage());
                Log::error('Scheduled media backup failed: ' . $e->getMessage());
            }
        }

        // Clean old backups if requested
        if ($this->option('clean')) {
            $this->info('🧹 Cleaning old backups...');
            $deleted = $this->backupService->cleanOldBackups(config('backup.files.retention_count', 10));
            $this->info("🗑️  Deleted {$deleted} old backup(s)");
        }

        $this->info('✨ Backup process completed!');

        return Command::SUCCESS;
    }

    /**
     * Ensure backup directory exists
     */
    protected function ensureBackupDirectory()
    {
        $backupPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
            $this->info('📁 Created backup directory');
        }
    }

    /**
     * Backup bills data to JSON
     */
    protected function backupBillsData()
    {
        $modelClass = config('backup.model.class', \App\Models\Bill::class);
        $relationships = config('backup.model.relationships', []);
        $includeTrashed = config('backup.model.include_soft_deleted', true);

        // Fetch bills with relationships
        $query = $modelClass::with($relationships);

        if ($includeTrashed) {
            $query->withTrashed();
        }

        $bills = $query->get();

        // Convert to array
        $exportData = $bills->map(function ($bill) {
            $data = $bill->toArray();

            // Format dates
            if (isset($data['date']) && $data['date']) {
                $data['date'] = date('Y-m-d', strtotime($data['date']));
            }

            return $data;
        });

        $jsonData = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $filename = 'bills_backup_' . date('Y-m-d_His') . '.json';
        $filepath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $filename);

        file_put_contents($filepath, $jsonData);

        $this->line("   📄 Saved: {$filename}");
        $this->line("   📊 Records: " . $bills->count());
    }

    /**
     * Backup media files to ZIP
     */
    protected function backupMediaFiles()
    {
        $sourceFolder = storage_path('app' . DIRECTORY_SEPARATOR . config('backup.paths.media_folder', 'public'));

        if (!file_exists($sourceFolder)) {
            $this->warn("   ⚠️  Media folder does not exist: {$sourceFolder}");
            return;
        }

        $zipFilename = 'public_media_' . date('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $zipFilename);

        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP file');
        }

        $files = $this->backupService->getFilesRecursive($sourceFolder);

        foreach ($files as $file) {
            $relativePath = str_replace($sourceFolder . DIRECTORY_SEPARATOR, '', $file);
            $zip->addFile($file, $relativePath);
        }

        $fileCount = $zip->numFiles;
        $zip->close();

        if ($fileCount === 0) {
            unlink($zipPath);
            $this->warn("   ⚠️  No media files found to backup");
            return;
        }

        $this->line("   📦 Saved: {$zipFilename}");
        $this->line("   📊 Files: {$fileCount}");
    }
}

