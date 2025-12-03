<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    public function metrics()
    {
        $paths = [
            'logs' => storage_path('logs'),
            'bill_attachments' => storage_path('app/public/bills'),
            'profile_images' => storage_path('app/public/profiles'),
        ];

        $metrics = [];
        foreach ($paths as $key => $path) {
            $metrics[$key] = $this->dirSize($path);
        }

        return view('storage.metrics', compact('metrics'));
    }

    public function clear(Request $request)
    {
        $target = $request->validate(['target' => 'required|in:logs,bill_attachments,profile_images'])['target'];
        $map = [
            'logs' => storage_path('logs'),
            'bill_attachments' => storage_path('app/public/bills'),
            'profile_images' => storage_path('app/public/profiles'),
        ];

        $path = $map[$target];
        if (is_dir($path)) {
            $files = glob($path . DIRECTORY_SEPARATOR . '*');
            foreach ($files as $file) {
                if (is_file($file)) @unlink($file);
                if (is_dir($file)) $this->rrmdir($file);
            }
        }

        return back()->with('status', 'Cleared ' . $target);
    }

    private function dirSize(string $path): int
    {
        $size = 0;
        if (! is_dir($path)) return 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) $size += $file->getSize();
        }
        return $size;
    }

    private function rrmdir(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $target = "$dir/{$file}";
            if (is_dir($target)) $this->rrmdir($target); else @unlink($target);
        }
        @rmdir($dir);
    }
}
