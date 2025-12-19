<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('module')) {
            $query->where('model', $request->module);
        }

        $logs = $query->latest()->paginate(50)->withQueryString();

        $actions = ['created', 'updated', 'deleted'];
        $modules = ActivityLog::distinct('model')->pluck('model')->sort();

        return view('activity_logs.index', compact('logs', 'actions', 'modules'));
    }
}
