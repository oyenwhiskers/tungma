<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Backup Service - Helper functions for backup operations
 * MODIFY HERE: Adjust paths, file patterns, or add custom logic
 */
class BackupService
{
    /**
     * Get all files recursively from a directory
     */
    public function getFilesRecursive($directory)
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    /**
     * List all backup files
     */
    public function listBackups()
    {
        $backupPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');

        if (!file_exists($backupPath)) {
            return [];
        }

        $files = scandir($backupPath, SCANDIR_SORT_DESCENDING);
        $backups = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $backupPath . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath)) {
                $backups[] = [
                    'filename' => $file,
                    'size' => filesize($filePath),
                    'size_human' => $this->formatBytes(filesize($filePath)),
                    'date' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'type' => $this->getBackupType($file),
                ];
            }
        }

        return $backups;
    }

    /**
     * Determine backup type from filename
     */
    protected function getBackupType($filename)
    {
        if (strpos($filename, 'media') !== false) {
            return 'Media';
        } elseif (strpos($filename, 'bills') !== false || strpos($filename, 'backup') !== false) {
            return 'Data';
        }
        return 'Unknown';
    }

    /**
     * Format bytes to human readable format
     */
    public function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Backup existing media before overwriting
     */
    public function backupExistingMedia($sourceFolder)
    {
        if (!file_exists($sourceFolder)) {
            return false;
        }

        $zipFilename = 'bills_media_pre_restore_' . date('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $zipFilename);

        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $files = $this->getFilesRecursive($sourceFolder);

        foreach ($files as $file) {
            $relativePath = str_replace($sourceFolder . DIRECTORY_SEPARATOR, '', $file);
            $zip->addFile($file, $relativePath);
        }

        $zip->close();

        return true;
    }

    /**
     * Clean old backups (keep only recent N backups)
     * MODIFY HERE: Adjust retention policy
     */
    public function cleanOldBackups($keepCount = 10)
    {
        $backups = $this->listBackups();

        if (count($backups) <= $keepCount) {
            return 0;
        }

        $backupPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        $deleted = 0;

        // Skip the first $keepCount backups (most recent)
        $toDelete = array_slice($backups, $keepCount);

        foreach ($toDelete as $backup) {
            $filePath = $backupPath . DIRECTORY_SEPARATOR . $backup['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
                $deleted++;
            }
        }

        return $deleted;
    }
}

