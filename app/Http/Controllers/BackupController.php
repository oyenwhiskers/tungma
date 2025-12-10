<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display the backup/restore management page
     */
    public function index()
    {
        // Ensure backup directory exists
        $this->ensureBackupDirectory();
        
        // Get list of existing backups
        $backups = $this->backupService->listBackups();
        
        // Calculate storage metrics
        $metrics = [
            'backups' => $this->getDirSize(storage_path('app' . DIRECTORY_SEPARATOR . 'backups')),
            'media' => $this->getDirSize(storage_path('app' . DIRECTORY_SEPARATOR . 'public')),
            'logs' => $this->getDirSize(storage_path('logs')),
        ];
        
        return view('backup.index', compact('backups', 'metrics'));
    }

    /**
     * Calculate directory size
     */
    protected function getDirSize($path)
    {
        $size = 0;
        if (!is_dir($path)) return 0;
        
        try {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to calculate directory size: ' . $e->getMessage());
        }
        
        return $size;
    }

    /**
     * Ensure backup directory exists
     */
    protected function ensureBackupDirectory()
    {
        $backupPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
    }

    /**
     * Export everything (data + media) in one ZIP
     */
    public function exportAll()
    {
        try {
            $timestamp = date('Y-m-d_His');
            $zipFilename = 'complete_backup_' . $timestamp . '.zip';
            $backupDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
            $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipFilename;

            // Ensure backup directory exists
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Step 1: Generate JSON data
            $bills = Bill::with(['company', 'courierPolicy'])
                ->withTrashed()
                ->get();

            $exportData = $bills->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'bill_code' => $bill->bill_code,
                    'date' => $bill->date?->format('Y-m-d'),
                    'amount' => $bill->amount,
                    'description' => $bill->description,
                    'payment_details' => $bill->payment_details,
                    'customer_info' => $bill->customer_info,
                    'courier_policy_id' => $bill->courier_policy_id,
                    'company_id' => $bill->company_id,
                    'eta' => $bill->eta,
                    'sst_details' => $bill->sst_details,
                    'policy_snapshot' => $bill->policy_snapshot,
                    'media_attachment' => $bill->media_attachment,
                    'created_at' => $bill->created_at?->toDateTimeString(),
                    'updated_at' => $bill->updated_at?->toDateTimeString(),
                    'deleted_at' => $bill->deleted_at?->toDateTimeString(),
                    'company_name' => $bill->company?->name,
                    'courier_policy_name' => $bill->courierPolicy?->name,
                ];
            });

            $jsonData = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Step 2: Create ZIP with JSON and media
            $zip = new ZipArchive();
            
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Failed to create ZIP file');
            }

            // Add JSON data file to ZIP
            $zip->addFromString('bills_data.json', $jsonData);

            // Add all media files from storage/app/public/
            $mediaFolder = storage_path('app' . DIRECTORY_SEPARATOR . 'public');
            
            if (file_exists($mediaFolder)) {
                $mediaFiles = $this->backupService->getFilesRecursive($mediaFolder);
                
                foreach ($mediaFiles as $file) {
                    $relativePath = 'public' . DIRECTORY_SEPARATOR . str_replace($mediaFolder . DIRECTORY_SEPARATOR, '', $file);
                    $zip->addFile($file, $relativePath);
                }
            }

            $totalFiles = $zip->numFiles;
            $zip->close();

            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(false);

        } catch (\Exception $e) {
            Log::error('Complete backup failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to create complete backup: ' . $e->getMessage());
        }
    }

    /**
     * Export bills data to JSON
     * Exports selected model data with relationships
     */
    public function exportData()
    {
        try {
            // Fetch bills with relationships
            // MODIFY HERE: Add/remove relationships as needed
            $bills = Bill::with(['company', 'courierPolicy'])
                ->withTrashed() // Include soft-deleted records
                ->get();

            // Convert to array and prepare for JSON export
            $exportData = $bills->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'bill_code' => $bill->bill_code,
                    'date' => $bill->date?->format('Y-m-d'),
                    'amount' => $bill->amount,
                    'description' => $bill->description,
                    'payment_details' => $bill->payment_details,
                    'customer_info' => $bill->customer_info,
                    'courier_policy_id' => $bill->courier_policy_id,
                    'company_id' => $bill->company_id,
                    'eta' => $bill->eta,
                    'sst_details' => $bill->sst_details,
                    'policy_snapshot' => $bill->policy_snapshot,
                    'media_attachment' => $bill->media_attachment,
                    'created_at' => $bill->created_at?->toDateTimeString(),
                    'updated_at' => $bill->updated_at?->toDateTimeString(),
                    'deleted_at' => $bill->deleted_at?->toDateTimeString(),
                    // MODIFY HERE: Add relationship data if needed
                    'company_name' => $bill->company?->name,
                    'courier_policy_name' => $bill->courierPolicy?->name,
                ];
            });

            $jsonData = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $filename = 'bills_backup_' . date('Y-m-d_His') . '.json';

            // Ensure backup directory exists
            $backupDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Store backup in storage/app/backups
            $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($filepath, $jsonData);

            return response()->download(
                $filepath,
                $filename,
                ['Content-Type' => 'application/json']
            )->deleteFileAfterSend(false); // Keep file for records

        } catch (\Exception $e) {
            Log::error('Data export failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }

    /**
     * Export media files as ZIP
     * Zips the storage/app/bills/ folder
     */
    public function exportMedia()
    {
        try {
            // MODIFY HERE: Change folder path if needed
            $sourceFolder = storage_path('app' . DIRECTORY_SEPARATOR . 'public');
            $zipFilename = 'public_media_' . date('Y-m-d_His') . '.zip';
            $backupDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
            $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipFilename;

            // Ensure backup directory exists
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Check if source folder exists
            if (!file_exists($sourceFolder)) {
                return back()->with('warning', 'Media folder does not exist. No media to backup.');
            }

            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Failed to create ZIP file');
            }

            // Add files to ZIP
            $files = $this->backupService->getFilesRecursive($sourceFolder);

            foreach ($files as $file) {
                $relativePath = str_replace($sourceFolder . DIRECTORY_SEPARATOR, '', $file);
                $zip->addFile($file, $relativePath);
            }

            $fileCount = $zip->numFiles;
            $zip->close();

            if ($fileCount === 0) {
                unlink($zipPath);
                return back()->with('warning', 'No media files found to backup.');
            }

            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(false);

        } catch (\Exception $e) {
            Log::error('Media export failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to export media: ' . $e->getMessage());
        }
    }

    /**
     * Import bills data from JSON
     * Restores data from uploaded JSON file
     */
    public function importData(Request $request)
    {
        $request->validate([
            'data_file' => 'required|file|mimes:json,txt|max:51200', // 50MB max
        ]);

        try {
            $file = $request->file('data_file');
            $jsonContent = file_get_contents($file->getRealPath());
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file: ' . json_last_error_msg());
            }

            if (!is_array($data)) {
                throw new \Exception('JSON file must contain an array of records');
            }

            $importedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;

            DB::beginTransaction();

            foreach ($data as $record) {
                try {
                    // Check if bill exists by ID or bill_code
                    $existing = Bill::withTrashed()->find($record['id']);

                    // MODIFY HERE: Adjust field mapping as needed
                    $billData = [
                        'bill_code' => $record['bill_code'] ?? null,
                        'date' => $record['date'] ?? null,
                        'amount' => $record['amount'] ?? 0,
                        'description' => $record['description'] ?? null,
                        'payment_details' => $record['payment_details'] ?? null,
                        'customer_info' => $record['customer_info'] ?? null,
                        'courier_policy_id' => $record['courier_policy_id'] ?? null,
                        'company_id' => $record['company_id'] ?? null,
                        'eta' => $record['eta'] ?? null,
                        'sst_details' => $record['sst_details'] ?? null,
                        'policy_snapshot' => $record['policy_snapshot'] ?? null,
                        'media_attachment' => $record['media_attachment'] ?? null,
                    ];

                    if ($existing) {
                        // Update existing record
                        $existing->update($billData);
                        
                        // Handle soft-delete state
                        if (isset($record['deleted_at'])) {
                            if ($record['deleted_at'] === null && $existing->trashed()) {
                                // Should not be deleted, but is - restore it
                                $existing->restore();
                            } elseif ($record['deleted_at'] !== null && !$existing->trashed()) {
                                // Should be deleted, but isn't - soft delete it
                                $existing->deleted_at = $record['deleted_at'];
                                $existing->save();
                            } elseif ($record['deleted_at'] !== null && $existing->trashed()) {
                                // Already deleted, just update the deleted_at timestamp
                                $existing->deleted_at = $record['deleted_at'];
                                $existing->save();
                            }
                        }
                        
                        $updatedCount++;
                    } else {
                        // Create new record with original ID
                        $bill = new Bill($billData);
                        $bill->id = $record['id'];
                        $bill->created_at = $record['created_at'] ?? now();
                        $bill->updated_at = $record['updated_at'] ?? now();
                        $bill->deleted_at = $record['deleted_at'] ?? null;
                        $bill->save();
                        
                        $importedCount++;
                    }

                } catch (\Exception $e) {
                    Log::warning("Failed to import bill record: " . $e->getMessage(), ['record' => $record]);
                    $skippedCount++;
                }
            }

            DB::commit();

            $message = "Data import completed. Created: {$importedCount}, Updated: {$updatedCount}, Skipped: {$skippedCount}";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Data import failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to import data: ' . $e->getMessage());
        }
    }

    /**
     * Import media files from ZIP
     * Extracts uploaded ZIP into storage/app/bills/
     */
    public function importMedia(Request $request)
    {
        $request->validate([
            'media_file' => 'required|file|mimes:zip|max:512000', // 500MB max
        ]);

        try {
            $file = $request->file('media_file');
            
            // MODIFY HERE: Change destination folder if needed
            $destinationFolder = storage_path('app' . DIRECTORY_SEPARATOR . 'public');

            // Create destination folder if it doesn't exist
            if (!file_exists($destinationFolder)) {
                mkdir($destinationFolder, 0755, true);
            }

            $zip = new ZipArchive();

            if ($zip->open($file->getRealPath()) !== true) {
                throw new \Exception('Failed to open ZIP file');
            }

            // Optional: Backup existing media before extraction
            if ($request->has('backup_existing')) {
                $this->backupService->backupExistingMedia($destinationFolder);
            }

            // Extract files
            $zip->extractTo($destinationFolder);
            $extractedCount = $zip->numFiles;
            $zip->close();

            return back()->with('success', "Media import completed. Extracted {$extractedCount} files.");

        } catch (\Exception $e) {
            Log::error('Media import failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to import media: ' . $e->getMessage());
        }
    }

    /**
     * Import everything (data + media) from one ZIP
     */
    public function importAll(Request $request)
    {
        $request->validate([
            'complete_file' => 'required|file|mimes:zip|max:512000', // 500MB max
        ]);

        try {
            $file = $request->file('complete_file');
            $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp_restore_' . time());
            
            // Create temp directory
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Extract ZIP to temp directory
            $zip = new ZipArchive();
            
            if ($zip->open($file->getRealPath()) !== true) {
                throw new \Exception('Failed to open ZIP file');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            $importedCount = 0;
            $updatedCount = 0;
            $mediaCount = 0;

            // Step 1: Import JSON data
            $jsonFile = $tempDir . DIRECTORY_SEPARATOR . 'bills_data.json';
            
            if (file_exists($jsonFile)) {
                $jsonContent = file_get_contents($jsonFile);
                $data = json_decode($jsonContent, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    DB::beginTransaction();

                    foreach ($data as $record) {
                        try {
                            $existing = Bill::withTrashed()->find($record['id']);
                            
                            $billData = [
                                'bill_code' => $record['bill_code'] ?? null,
                                'date' => $record['date'] ?? null,
                                'amount' => $record['amount'] ?? 0,
                                'description' => $record['description'] ?? null,
                                'payment_details' => $record['payment_details'] ?? null,
                                'customer_info' => $record['customer_info'] ?? null,
                                'courier_policy_id' => $record['courier_policy_id'] ?? null,
                                'company_id' => $record['company_id'] ?? null,
                                'eta' => $record['eta'] ?? null,
                                'sst_details' => $record['sst_details'] ?? null,
                                'policy_snapshot' => $record['policy_snapshot'] ?? null,
                                'media_attachment' => $record['media_attachment'] ?? null,
                            ];

                            if ($existing) {
                                $existing->update($billData);
                                
                                // Handle soft-delete state
                                if (isset($record['deleted_at'])) {
                                    if ($record['deleted_at'] === null && $existing->trashed()) {
                                        // Should not be deleted, but is - restore it
                                        $existing->restore();
                                    } elseif ($record['deleted_at'] !== null && !$existing->trashed()) {
                                        // Should be deleted, but isn't - soft delete it
                                        $existing->deleted_at = $record['deleted_at'];
                                        $existing->save();
                                    } elseif ($record['deleted_at'] !== null && $existing->trashed()) {
                                        // Already deleted, just update the deleted_at timestamp
                                        $existing->deleted_at = $record['deleted_at'];
                                        $existing->save();
                                    }
                                }
                                
                                $updatedCount++;
                            } else {
                                $bill = new Bill($billData);
                                $bill->id = $record['id'];
                                $bill->created_at = $record['created_at'] ?? now();
                                $bill->updated_at = $record['updated_at'] ?? now();
                                $bill->deleted_at = $record['deleted_at'] ?? null;
                                $bill->save();
                                
                                $importedCount++;
                            }

                        } catch (\Exception $e) {
                            Log::warning("Failed to import bill record: " . $e->getMessage());
                        }
                    }

                    DB::commit();
                }
            }

            // Step 2: Restore media files
            $publicDir = $tempDir . DIRECTORY_SEPARATOR . 'public';
            
            if (file_exists($publicDir)) {
                $destinationFolder = storage_path('app' . DIRECTORY_SEPARATOR . 'public');
                
                // Backup existing media if requested
                if ($request->has('backup_existing')) {
                    $this->backupService->backupExistingMedia($destinationFolder);
                }

                // Copy files from temp to storage/app/public
                $this->recursiveCopy($publicDir, $destinationFolder);
                
                // Count media files
                $mediaFiles = $this->backupService->getFilesRecursive($publicDir);
                $mediaCount = count($mediaFiles);
            }

            // Clean up temp directory
            $this->deleteDirectory($tempDir);

            $message = "Complete restore successful! Data: {$importedCount} created, {$updatedCount} updated. Media: {$mediaCount} files restored.";
            
            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Complete restore failed: ' . $e->getMessage());
            
            // Clean up temp directory on error
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            
            return back()->with('error', 'Failed to restore backup: ' . $e->getMessage());
        }
    }

    /**
     * Recursively copy directory
     */
    protected function recursiveCopy($source, $destination)
    {
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $dir = opendir($source);
        
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;

            if (is_dir($sourcePath)) {
                $this->recursiveCopy($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }

        closedir($dir);
    }

    /**
     * Recursively delete directory
     */
    protected function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        try {
            $filename = basename($request->filename); // Security: prevent directory traversal
            $filePath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $filename);

            if (file_exists($filePath)) {
                unlink($filePath);
                return back()->with('success', 'Backup file deleted successfully.');
            }

            return back()->with('error', 'Backup file not found.');

        } catch (\Exception $e) {
            Log::error('Backup deletion failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }

    /**
     * Clear storage area
     */
    public function clearStorage(Request $request)
    {
        $request->validate([
            'target' => 'required|in:backups,media,logs'
        ]);

        try {
            $target = $request->target;
            $pathMap = [
                'backups' => storage_path('app' . DIRECTORY_SEPARATOR . 'backups'),
                'media' => storage_path('app' . DIRECTORY_SEPARATOR . 'public'),
                'logs' => storage_path('logs'),
            ];

            $path = $pathMap[$target];

            if (!is_dir($path)) {
                return back()->with('warning', 'Storage area does not exist.');
            }

            $deletedCount = 0;
            $files = glob($path . DIRECTORY_SEPARATOR . '*');
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $deletedCount++;
                } elseif (is_dir($file)) {
                    $this->deleteDirectory($file);
                    $deletedCount++;
                }
            }

            $labels = [
                'backups' => 'Backups',
                'media' => 'Media Files',
                'logs' => 'Logs',
            ];

            return back()->with('success', "Cleared {$labels[$target]}. Deleted {$deletedCount} items.");

        } catch (\Exception $e) {
            Log::error('Storage clear failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear storage: ' . $e->getMessage());
        }
    }
}

