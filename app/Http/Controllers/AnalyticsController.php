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
        $selectedYear = $request->input('year');
        $selectedMonth = $request->input('month');
        $selectedDay = $request->input('day');

        // Apply filters to a base query for bills
        $billQuery = Bill::query();

        if ($selectedYear) {
            $billQuery->whereYear('created_at', $selectedYear);
        }
        if ($selectedMonth) {
            $billQuery->whereMonth('created_at', $selectedMonth);
        }
        if ($selectedDay) {
            $billQuery->whereDay('created_at', $selectedDay);
        }

        // Get available years for filter
        $years = Bill::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Total revenue (filtered)
        $totalRevenue = (clone $billQuery)->sum('amount');

        // Staff distribution per company
        // Note: Staff is usually a current state, so we might not want to filter by bill dates here.
        // Keeping it as is for now.
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

        // Bill summaries by company (filtered)
        $billSummaries = (clone $billQuery)
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

        $revenueTrend = (clone $billQuery)
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
            'filter',
            'years',
            'selectedYear',
            'selectedMonth',
            'selectedDay'
        ));
    }
}
