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

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $sortDirection = $request->query('sort', 'desc');
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $logs = $query->orderBy('created_at', $sortDirection)->paginate(50)->withQueryString();

        $actions = ['created', 'updated', 'deleted'];
        $modules = ActivityLog::distinct('model')->pluck('model')->sort();

        return view('activity_logs.index', compact('logs', 'actions', 'modules'));
    }
}
