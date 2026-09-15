<?php

/**
 * Backup System Configuration
 *
 * Configures the queued backup and restore system, database dumpers,
 * chunk sizes, models, image columns, and storage paths.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which Eloquent model represents receipts/bills and which
    | columns store relative image paths on the disk.
    |
    */
    'model' => [
        'class' => env('BACKUP_MODEL', class_exists(\App\Models\Bill::class) ? \App\Models\Bill::class : \App\Models\Receipt::class),
        'image_columns' => [
            'media_attachment',
            'payment_proof_attachment',
            'image_path',
        ],
        'disk' => env('BACKUP_DISK', 'public'),
        'chunk_size' => (int) env('BACKUP_CHUNK_SIZE', 250),
        'include_all_public_files' => env('BACKUP_ALL_PUBLIC_FILES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Dump Settings
    |--------------------------------------------------------------------------
    |
    | If exec() or mysqldump is not available on this host (cPanel shared hosting),
    | the system automatically falls back to a streaming pure-PHP database dumper.
    |
    */
    'database' => [
        'force_php_dump' => env('BACKUP_FORCE_PHP_DUMP', false),
        'chunk_rows' => (int) env('BACKUP_DB_CHUNK_ROWS', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Define where backups and media files are stored relative to storage/app/
    |
    */
    'paths' => [
        'backup_storage' => 'backups', // Relative to storage/app/
        'media_folder' => 'public',    // Relative to storage/app/
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | The backup and restore jobs run asynchronously on this queue.
    |
    */
    'queue' => [
        'connection' => env('BACKUP_QUEUE_CONNECTION', null),
        'queue' => env('BACKUP_QUEUE_NAME', 'default'),
        'timeout' => 3600, // 1 hour maximum
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention & Safety
    |--------------------------------------------------------------------------
    |
    | Confirmation phrase for destructive restore and retention policy.
    |
    */
    'restore_confirmation_phrase' => 'RESTORE',
    'retention_count' => (int) env('BACKUP_RETENTION_COUNT', 15),

];
