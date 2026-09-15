<?php

namespace App\Http\Controllers;

use App\Jobs\CreateBackupJob;
use App\Jobs\RestoreBackupJob;
use App\Models\Bill;
use App\Services\BackupProgressManager;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    protected BackupService $backupService;
    protected BackupProgressManager $progressManager;

    public function __construct(BackupService $backupService, BackupProgressManager $progressManager)
    {
        $this->backupService = $backupService;
        $this->progressManager = $progressManager;
    }

    /**
     * Display the backup/restore management page
     */
    public function index()
    {
        $this->ensureBackupDirectory();

        // List of backups
        $backups = $this->backupService->listBackups();

        // Calculate storage metrics
        $metrics = [
            'backups' => $this->getDirSize(storage_path('app' . DIRECTORY_SEPARATOR . 'backups')),
            'media' => $this->getDirSize(public_path('storage')),
            'logs' => $this->getDirSize(storage_path('logs')),
        ];

        // Fetch latest active or recent job
        $activeJob = $this->progressManager->getLatest();

        // Server PHP settings for upload limits
        $serverLimits = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
        ];

        $confirmPhrase = config('backup.restore_confirmation_phrase', 'RESTORE');

        return view('backup.index', compact('backups', 'metrics', 'activeJob', 'serverLimits', 'confirmPhrase'));
    }

    /**
     * Dispatch queued backup job
     */
    public function backupNow(Request $request)
    {
        try {
            $this->ensureBackupDirectory();

            $jobId = 'backup_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 6);

            $this->progressManager->init($jobId, 'backup', 'Creating Full System Backup');

            CreateBackupJob::dispatch($jobId)->onQueue(config('backup.queue.queue', 'default'));

            $this->tryKickstartQueueWorker();

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Backup job has been queued in background.',
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to dispatch backup job: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dispatch queued restore job
     */
    public function restoreNow(Request $request)
    {
        $expectedPhrase = config('backup.restore_confirmation_phrase', 'RESTORE');

        // Validation
        $request->validate([
            'confirm_phrase' => 'required|string',
            'restore_file' => 'nullable|file|mimes:zip|max:1048576', // up to 1GB if server permits
            'existing_backup' => 'nullable|string',
        ]);

        if (trim($request->confirm_phrase) !== $expectedPhrase) {
            return response()->json([
                'success' => false,
                'message' => "Confirmation phrase does not match. Please type \"{$expectedPhrase}\" to confirm.",
            ], 422);
        }

        try {
            $this->ensureBackupDirectory();
            $zipPath = null;
            $isUploadedTemp = false;

            if ($request->filled('uploaded_temp_filename')) {
                // File assembled via Chunked Uploader
                $safeFilename = basename($request->uploaded_temp_filename);
                $zipPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $safeFilename);

                if (!file_exists($zipPath)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Assembled backup file was not found on server.',
                    ], 404);
                }
                $isUploadedTemp = true;
            } elseif ($request->hasFile('restore_file')) {
                // Direct file upload fallback
                $file = $request->file('restore_file');
                $tempFilename = 'upload_restore_' . date('Ymd_His') . '_' . uniqid() . '.zip';
                $destination = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $tempFilename);

                $file->move(dirname($destination), basename($destination));
                $zipPath = $destination;
                $isUploadedTemp = true;
            } elseif ($request->filled('existing_backup')) {
                $safeFilename = basename($request->existing_backup);
                $zipPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $safeFilename);

                if (!file_exists($zipPath)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected existing backup file was not found on server.',
                    ], 404);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Please upload a backup ZIP file or choose an existing backup.',
                ], 422);
            }

            $jobId = 'restore_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 6);

            $this->progressManager->init($jobId, 'restore', 'Restoring Full System Backup');

            RestoreBackupJob::dispatch($jobId, $zipPath, $isUploadedTemp)->onQueue(config('backup.queue.queue', 'default'));

            $this->tryKickstartQueueWorker();

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Restore job has been queued in background.',
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to dispatch restore job: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start restore: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Poll status of a backup or restore job
     */
    public function jobStatus(string $jobId)
    {
        $state = $this->progressManager->get($jobId);

        if (!$state) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Job status not found.',
            ], 404);
        }

        return response()->json($state);
    }

    /**
     * Handle chunked file uploads (for large 10GB-20GB+ ZIP files)
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'file_id' => 'required|string',
            'chunk_index' => 'required|integer',
            'total_chunks' => 'required|integer',
            'filename' => 'required|string',
            'chunk' => 'required|file',
        ]);

        try {
            $fileId = preg_replace('/[^a-zA-Z0-9_-]/', '', $request->file_id);
            $chunkIndex = (int) $request->chunk_index;
            $totalChunks = (int) $request->total_chunks;

            $chunksDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'chunks' . DIRECTORY_SEPARATOR . $fileId);
            if (!file_exists($chunksDir)) {
                @mkdir($chunksDir, 0755, true);
            }

            // Save chunk to temp chunks directory
            $chunkFile = $request->file('chunk');
            $chunkFileName = 'chunk_' . str_pad($chunkIndex, 6, '0', STR_PAD_LEFT);
            $chunkFile->move($chunksDir, $chunkFileName);

            // Check if this was the last chunk
            if ($chunkIndex === $totalChunks - 1) {
                // Verify all chunks exist
                $allExist = true;
                for ($i = 0; $i < $totalChunks; $i++) {
                    $part = $chunksDir . DIRECTORY_SEPARATOR . 'chunk_' . str_pad($i, 6, '0', STR_PAD_LEFT);
                    if (!file_exists($part)) {
                        $allExist = false;
                        break;
                    }
                }

                if ($allExist) {
                    // Stream-assemble chunks into final file in storage/app/backups
                    $finalFilename = 'upload_restore_' . date('Ymd_His') . '_' . $fileId . '.zip';
                    $finalPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $finalFilename);

                    $out = fopen($finalPath, 'wb');
                    if (!$out) {
                        throw new \Exception('Failed to create assembled archive file on server.');
                    }

                    for ($i = 0; $i < $totalChunks; $i++) {
                        $part = $chunksDir . DIRECTORY_SEPARATOR . 'chunk_' . str_pad($i, 6, '0', STR_PAD_LEFT);
                        $in = fopen($part, 'rb');
                        while (!feof($in)) {
                            fwrite($out, fread($in, 65536));
                        }
                        fclose($in);
                        @unlink($part); // Immediately free disk space
                    }
                    fclose($out);
                    @rmdir($chunksDir);

                    return response()->json([
                        'success' => true,
                        'completed' => true,
                        'file_id' => $fileId,
                        'assembled_filename' => $finalFilename,
                        'size' => filesize($finalPath),
                        'message' => 'Archive assembled successfully on server.',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'completed' => false,
                'chunk_index' => $chunkIndex,
                'total_chunks' => $totalChunks,
            ]);

        } catch (\Throwable $e) {
            Log::error('Chunked upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Chunk upload error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a completed backup archive
     */
    public function downloadBackup(string $filename)
    {
        $safeFilename = basename($filename);
        $filePath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $safeFilename);

        if (!file_exists($filePath)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($filePath, $safeFilename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
        ]);

        try {
            $filename = basename($request->filename);
            $filePath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $filename);

            if (file_exists($filePath)) {
                unlink($filePath);
                return back()->with('success', 'Backup file deleted successfully.');
            }

            return back()->with('error', 'Backup file not found.');

        } catch (\Throwable $e) {
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
            'target' => 'required|in:backups,media,logs',
            'password' => 'required|string',
        ]);

        // Verify Super Admin password
        $user = auth()->user();
        if (!$user || $user->role !== 'super_admin') {
            return back()->with('error', 'Unauthorized access.');
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please re-enter your password to confirm.'])->withInput();
        }

        try {
            $target = $request->target;
            $pathMap = [
                'backups' => storage_path('app' . DIRECTORY_SEPARATOR . 'backups'),
                'media' => public_path('storage'),
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

        } catch (\Throwable $e) {
            Log::error('Storage clear failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to clear storage: ' . $e->getMessage());
        }
    }

    /**
     * Display bills for bulk deletion (Gmail-style selection)
     */
    public function deleteBills(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        // Build query with filters
        $query = Bill::with(['company', 'creator', 'checker']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('customer_info->name', 'like', "%{$search}%")
                    ->orWhere('customer_info->phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'paid') {
                $query->where('is_paid', true);
            } elseif ($request->payment_status === 'unpaid') {
                $query->where('is_paid', false);
            }
        }

        $bills = $query->latest()->paginate(50)->withQueryString();
        $companies = \App\Models\Company::all();

        return view('backup.delete-bills', compact('bills', 'companies'));
    }

    /**
     * Delete selected bills (bulk delete)
     */
    public function deleteSelectedBills(Request $request)
    {
        $request->validate([
            'bill_ids' => 'required|array',
            'bill_ids.*' => 'required|integer|exists:bills,id',
            'password' => 'required|string',
        ]);

        $user = auth()->user();
        if (!$user || $user->role !== 'super_admin') {
            return back()->with('error', 'Unauthorized access.');
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please re-enter your password to confirm.'])->withInput();
        }

        try {
            $billIds = $request->bill_ids;
            $deletedCount = Bill::whereIn('id', $billIds)->delete();

            Log::info("Super Admin deleted {$deletedCount} bills (IDs: " . implode(', ', $billIds) . ")");

            return back()->with('success', "Successfully deleted {$deletedCount} bill(s).");

        } catch (\Throwable $e) {
            Log::error('Delete selected bills failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete bills: ' . $e->getMessage());
        }
    }

    /**
     * Trigger queue processing on-demand (fallback if cron is delayed or for local dev)
     */
    public function processQueue(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--tries' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Queue processed successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Queue worker error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Try non-blocking background queue work invocation (supports both Windows & Linux)
     */
    protected function tryKickstartQueueWorker(): void
    {
        try {
            $phpBinary = PHP_BINARY ?: 'php';
            $artisan = base_path('artisan');

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows background process
                $cmd = sprintf('start /B "" %s %s queue:work --stop-when-empty', escapeshellarg($phpBinary), escapeshellarg($artisan));
                if (function_exists('popen') && function_exists('pclose')) {
                    @pclose(@popen($cmd, 'r'));
                }
            } else {
                // Linux / cPanel Unix background process
                if (function_exists('exec')) {
                    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
                    if (!in_array('exec', $disabled, true)) {
                        $cmd = sprintf('nohup %s %s queue:work --stop-when-empty > /dev/null 2>&1 &', escapeshellarg($phpBinary), escapeshellarg($artisan));
                        @exec($cmd);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore; cPanel cron will pick it up
        }
    }

    /**
     * Calculate directory size
     */
    protected function getDirSize(string $path): int
    {
        $size = 0;
        if (!is_dir($path)) {
            return 0;
        }

        try {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to calculate directory size: ' . $e->getMessage());
        }

        return $size;
    }

    /**
     * Ensure backup directory exists
     */
    protected function ensureBackupDirectory(): void
    {
        $backupPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        if (!file_exists($backupPath)) {
            @mkdir($backupPath, 0755, true);
        }
    }

    /**
     * Recursively delete directory
     */
    protected function deleteDirectory(string $dir): void
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
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
