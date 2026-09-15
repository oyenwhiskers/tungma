<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BackupProgressManager
{
    /**
     * Get storage directory for progress files
     */
    protected function getStorageDir(): string
    {
        $dir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get file path for a specific job's progress
     */
    protected function getFilePath(string $jobId): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $jobId);
        return $this->getStorageDir() . DIRECTORY_SEPARATOR . "progress_{$safeId}.json";
    }

    /**
     * Get pointer file path for the latest job
     */
    protected function getLatestPointerPath(): string
    {
        return $this->getStorageDir() . DIRECTORY_SEPARATOR . 'latest_job.json';
    }

    /**
     * Initialize a new progress tracking state
     */
    public function init(string $jobId, string $type = 'backup', string $title = 'Starting operation'): array
    {
        $state = [
            'job_id' => $jobId,
            'type' => $type,
            'title' => $title,
            'status' => 'queued',
            'stage' => 'Queued in background...',
            'percentage' => 0,
            'counter_text' => '0 / 0 items',
            'current_file' => '',
            'logs' => [
                [
                    'time' => date('H:i:s'),
                    'message' => "Job queued. Waiting for queue worker...",
                ],
            ],
            'result' => null,
            'error' => null,
            'started_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->save($jobId, $state);

        // Record as the latest job
        try {
            file_put_contents($this->getLatestPointerPath(), json_encode(['job_id' => $jobId, 'type' => $type]));
        } catch (\Throwable $e) {
            // Ignore pointer write failures
        }

        return $state;
    }

    /**
     * Update progress state
     */
    public function update(string $jobId, array $attributes): array
    {
        $state = $this->get($jobId) ?? [
            'job_id' => $jobId,
            'logs' => [],
        ];

        foreach ($attributes as $key => $value) {
            $state[$key] = $value;
        }

        $state['updated_at'] = date('Y-m-d H:i:s');
        $this->save($jobId, $state);

        return $state;
    }

    /**
     * Append a log message
     */
    public function log(string $jobId, string $message): void
    {
        $state = $this->get($jobId);
        if (!$state) {
            return;
        }

        $logs = $state['logs'] ?? [];
        $logs[] = [
            'time' => date('H:i:s'),
            'message' => $message,
        ];

        // Keep last 400 lines to avoid excessive memory
        if (count($logs) > 400) {
            $logs = array_slice($logs, -400);
        }

        $state['logs'] = $logs;
        $state['updated_at'] = date('Y-m-d H:i:s');

        $this->save($jobId, $state);
    }

    /**
     * Mark job as complete
     */
    public function complete(string $jobId, array $result = []): array
    {
        return $this->update($jobId, [
            'status' => 'completed',
            'stage' => 'Completed successfully',
            'percentage' => 100,
            'result' => $result,
        ]);
    }

    /**
     * Mark job as failed
     */
    public function fail(string $jobId, string $errorMessage): array
    {
        $this->log($jobId, "ERROR: {$errorMessage}");

        return $this->update($jobId, [
            'status' => 'failed',
            'stage' => 'Failed: ' . substr($errorMessage, 0, 80),
            'error' => $errorMessage,
        ]);
    }

    /**
     * Retrieve progress for a job
     */
    public function get(string $jobId): ?array
    {
        $filePath = $this->getFilePath($jobId);

        if (file_exists($filePath)) {
            $content = @file_get_contents($filePath);
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        // Fallback to cache if file was not found
        try {
            $cached = Cache::get("backup_progress_{$jobId}");
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            // Cache store might be temporarily offline during table replacement
        }

        return null;
    }

    /**
     * Get latest active or recent job
     */
    public function getLatest(): ?array
    {
        $pointerPath = $this->getLatestPointerPath();
        if (file_exists($pointerPath)) {
            $content = @file_get_contents($pointerPath);
            if ($content) {
                $data = json_decode($content, true);
                if (!empty($data['job_id'])) {
                    return $this->get($data['job_id']);
                }
            }
        }
        return null;
    }

    /**
     * Save progress to file and cache
     */
    protected function save(string $jobId, array $state): void
    {
        $filePath = $this->getFilePath($jobId);
        @file_put_contents($filePath, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Mirror to cache (with safe catch during DB rebuilds)
        try {
            Cache::put("backup_progress_{$jobId}", $state, 86400); // 24 hours
        } catch (\Throwable $e) {
            // Ignore cache write failures if DB tables are dropped during restore
        }
    }
}
