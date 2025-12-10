<?php

/**
 * Backup System Configuration
 *
 * Customize backup behavior by modifying values here
 * instead of editing controller code directly
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which model to backup and its relationships
    |
    */
    'model' => [
        'class' => \App\Models\Bill::class,
        'relationships' => ['company', 'courierPolicy'], // Load these relationships
        'include_soft_deleted' => true, // Include soft-deleted records
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Define where backups and media files are stored
    |
    */
    'paths' => [
        'backup_storage' => 'backups',      // Relative to storage/app/
        'media_folder' => 'public/bills',   // Relative to storage/app/
    ],

    /*
    |--------------------------------------------------------------------------
    | File Settings
    |--------------------------------------------------------------------------
    |
    | Configure file naming, size limits, and retention
    |
    */
    'files' => [
        'data_prefix' => 'bills_backup',      // Prefix for data export files
        'media_prefix' => 'bills_media',      // Prefix for media export files
        'date_format' => 'Y-m-d_His',         // Date format in filenames
        'retention_count' => 10,              // Number of backups to keep (0 = unlimited)
        'max_upload_size_mb' => [
            'data' => 50,                     // Max JSON upload size in MB
            'media' => 500,                   // Max ZIP upload size in MB
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configure what data to export
    |
    */
    'export' => [
        'fields' => [
            // List fields to export (empty array = export all fillable fields)
            // 'bill_code', 'date', 'amount', 'description', ...
        ],
        'exclude_fields' => [
            // Fields to exclude from export
            // 'internal_notes', 'password', ...
        ],
        'include_timestamps' => true,         // Export created_at, updated_at, deleted_at
        'include_relationships' => true,      // Export relationship data
        'json_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Settings
    |--------------------------------------------------------------------------
    |
    | Configure import/restore behavior
    |
    */
    'import' => [
        'update_existing' => true,            // Update existing records by ID
        'skip_invalid' => true,               // Skip invalid records instead of failing
        'validate_foreign_keys' => false,     // Validate company_id, policy_id exist
        'backup_before_restore' => false,     // Auto-backup media before importing
        'transaction' => true,                // Use database transaction
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    |
    | Apply filters to limit what data gets exported
    | Set to null or empty array to disable
    |
    */
    'filters' => [
        // Filter by company (null = all companies)
        'company_id' => null,

        // Filter by date range (null = all dates)
        'date_from' => null,  // '2025-01-01'
        'date_to' => null,    // '2025-12-31'

        // Filter by amount range
        'amount_min' => null,
        'amount_max' => null,

        // Custom where conditions (applied as is)
        'custom_where' => [
            // ['column', 'operator', 'value']
            // ['status', '=', 'active'],
            // ['amount', '>', 1000],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Enable/disable notifications for backup operations
    |
    */
    'notifications' => [
        'enabled' => false,
        'email' => env('BACKUP_NOTIFICATION_EMAIL', null),
        'notify_on' => [
            'export' => false,
            'import' => true,  // Notify on successful restore
            'error' => true,   // Notify on errors
        ],
    ],

];

