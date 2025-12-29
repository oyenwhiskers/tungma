<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = ActivityLog::with('user');

        // ADMIN: Only see logs from their company, excluding Super Admin activities
        if ($user->role === 'admin') {
            $query->where('company_id', $user->company_id)
                ->whereHas('user', function($q) {
                    $q->where('role', '!=', 'super_admin');
                });
        }

        // SUPER ADMIN: See all logs (no company filter)

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('module')) {
            $query->where('model', $request->module);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $sortDirection = $request->query('sort', 'desc');
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $logs = $query->orderBy('created_at', $sortDirection)->paginate(50)->withQueryString();

        $actions = ['created', 'updated', 'deleted'];
        
        // Filter modules based on role
        if ($user->role === 'admin') {
            $modules = ActivityLog::where('company_id', $user->company_id)
                ->whereHas('user', function($q) {
                    $q->where('role', '!=', 'super_admin');
                })
                ->distinct('model')
                ->pluck('model')
                ->sort();
        } else {
            $modules = ActivityLog::distinct('model')->pluck('model')->sort();
        }

        return view('activity_logs.index', compact('logs', 'actions', 'modules'));
    }
}
