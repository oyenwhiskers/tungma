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
            'company_id' => self::resolveCompanyId($model),
            'action' => $action,
            'model' => class_basename($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected static function resolveCompanyId($model)
    {
        // Priority 1: If the model itself has company_id (Bill, CourierPolicy, BusDepartures, etc.)
        if (isset($model->company_id)) {
            return $model->company_id;
        }
        
        // Priority 2: If the model IS a Company
        if ($model instanceof \App\Models\Company) {
            return $model->id;
        }
        
        // Priority 3: If the model is User with company_id
        if ($model instanceof \App\Models\User && isset($model->company_id)) {
            return $model->company_id;
        }
        
        // Priority 4: Get from authenticated user
        $user = auth()->user();
        if ($user && isset($user->company_id)) {
            return $user->company_id;
        }
        
        // Fallback: null (for Super Admin or system actions)
        return null;
    }
}
