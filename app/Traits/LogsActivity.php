<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            self::logActivity('created', $model);
        });

        static::updated(function ($model) {
            self::logActivity('updated', $model);
        });

        static::deleted(function ($model) {
            self::logActivity('deleted', $model);
        });
    }

    protected static function logActivity($action, $model)
    {
        $oldValues = null;
        $newValues = null;

        if ($action === 'created') {
            $newValues = $model->getAttributes();
        } elseif ($action === 'updated') {
            $oldValues = $model->getOriginal();
            $newValues = $model->getAttributes();
        } elseif ($action === 'deleted') {
            $oldValues = $model->getAttributes();
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model' => class_basename($model),
            'model_id' => $model->id,
            'old_values' => $oldValues, // Casts will handle json_encode if passed as array, but let's check model casts
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
