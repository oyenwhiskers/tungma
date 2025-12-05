<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleBasedAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admin has full access
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Admin restricted to company management and staff
        if ($user->role === 'admin') {
            // Block admin from accessing super admin routes
            if ($request->routeIs('companies.*') && !$request->routeIs('companies.show')) {
                abort(403, 'Admins cannot manage companies');
            }
            if ($request->routeIs('admins.*')) {
                abort(403, 'Admins cannot manage other admins');
            }
            if ($request->routeIs('storage.*')) {
                abort(403, 'Unauthorized');
            }
        }

        // Staff cannot create/edit/delete anything
        if ($user->role === 'staff') {
            if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch') || $request->isMethod('delete')) {
                abort(403, 'Staff cannot modify records');
            }
        }

        return $next($request);
    }
}
