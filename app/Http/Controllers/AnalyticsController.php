<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $filter = $request->input('filter', 'daily');

        // Total revenue
        $totalRevenue = Bill::query()->sum('amount');

        // Staff distribution per company
        $staffDistribution = User::query()
            ->select('company_id', DB::raw('count(*) as total'))
            ->where('role', 'staff')
            ->groupBy('company_id')
            ->get()
            ->map(function ($row) {
                return [
                    'company' => optional(Company::find($row->company_id))->name ?? 'Unassigned',
                    'total' => $row->total,
                ];
            });

        // Bill summaries by company
        $billSummaries = Bill::query()
            ->select('company_id', DB::raw('count(*) as bills'), DB::raw('sum(amount) as revenue'))
            ->groupBy('company_id')
            ->get()
            ->map(function ($row) {
                return [
                    'company' => optional(Company::find($row->company_id))->name ?? 'Unassigned',
                    'bills' => (int) $row->bills,
                    'revenue' => (float) $row->revenue,
                ];
            });

        // Revenue trend
        $dateFormat = match ($filter) {
            'daily' => '%Y-%m-%d',
            'yearly' => '%Y',
            default => '%Y-%m',
        };

        $revenueTrend = Bill::query()
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as label"),
                DB::raw('sum(amount) as revenue')
            )
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(function ($row) {
                return [
                    'label' => $row->label,
                    'revenue' => (float) $row->revenue,
                ];
            });

        return view('analytics.index', compact(
            'totalRevenue',
            'staffDistribution',
            'billSummaries',
            'revenueTrend',
            'filter'
        ));
    }
}
