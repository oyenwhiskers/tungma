<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backup Access Middleware
 * 
 * Optional: Use this middleware for fine-grained backup access control
 * Currently, backup routes use 'super.admin' middleware
 * 
 * To use this middleware:
 * 1. Register in app/Http/Kernel.php
 * 2. Replace 'super.admin' with 'backup.access' in routes
 */
class BackupAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to access backups.');
        }

        $user = auth()->user();

        // CUSTOMIZE HERE: Define who can access backups
        
        // Option 1: Only Super Admin (current default)
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Option 2: Super Admin + Admin (uncomment to enable)
        // if ($user->isSuperAdmin() || $user->isAdmin()) {
        //     return $next($request);
        // }

        // Option 3: Check specific permission (if using Spatie Laravel-Permission)
        // if ($user->can('manage-backups')) {
        //     return $next($request);
        // }

        // Option 4: Check custom role field
        // if (in_array($user->role, ['super_admin', 'backup_manager'])) {
        //     return $next($request);
        // }

        // Option 5: Allow users to backup only their company data
        // if ($request->routeIs('backup.export.*')) {
        //     // Allow export for own company
        //     return $next($request);
        // }

        // Deny access
        abort(403, 'You do not have permission to access backup management.');
    }
}

