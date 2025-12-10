<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Carbon\Carbon;

/**
 * @group Dashboard API
 *
 * Dashboard management API for the application.
 *
 * This API provides endpoints for retrieving dashboard metrics and statistics.
 *
 * @header Authorization Bearer {token} Example: Bearer 1|abc123def456...
 * @authenticated
 */
class DashboardController extends Controller
{
    /**
     * Get dashboard metrics.
     *
     * Retrieve summary statistics for bills, including daily and monthly counts for active and voided bills.
     *
     * @group Dashboard
     * @authenticated
     * @header Authorization Bearer {token}
     *
     * @response 200 {
     *     "success": true,
     *     "data": {
     *         "total_bills": 100,
     *         "void_bills_today": 2,
     *         "bills_created_today": 5,
     *         "total_void_bills_this_month": 10,
     *         "total_bills_this_month": 50
     *     }
     * }
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_bills' => Bill::count(),
                'void_bills_today' => Bill::onlyTrashed()->whereDate('deleted_at', Carbon::today())->count(),
                'bills_created_today' => Bill::whereDate('created_at', Carbon::today())->count(),
                'total_void_bills_this_month' => Bill::onlyTrashed()->whereMonth('deleted_at', Carbon::now()->month)->whereYear('deleted_at', Carbon::now()->year)->count(),
                'total_bills_this_month' => Bill::whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->count(),
            ],
        ]);
    }

    /**
     * Get daily analytics.
     *
     * Retrieve daily counts of bills created (active) and voided bills for the specified month and year.
     * Use this data to generate a stacked bar chart.
     *
     * @group Dashboard
     * @authenticated
     * @header Authorization Bearer {token}
     * 
     * @urlParam month integer The month number (1-12). Defaults to current month. Example: 12
     * @urlParam year integer The year. Defaults to current year. Example: 2025
     *
     * @response 200 {
     *     "success": true,
     *     "data": [
     *         {
     *             "date": "2025-12-01",
     *             "void_bills": 1,
     *             "bills_created": 5
     *         }
     *     ]
     * }
     */
    public function dailyAnalytic($month = null, $year = null)
    {
        $month = $month ?: Carbon::now()->month;
        $year = $year ?: Carbon::now()->year;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        $dailyStats = [];
        $currentDate = $startDate->copy();

        // Initialize all dates in the month with 0 values
        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $dailyStats[$dateString] = [
                'date' => $dateString,
                'void_bills' => 0,
                'bills_created' => 0,
            ];
            $currentDate->addDay();
        }

        // Fetch active bills created per day
        $createdBills = Bill::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        // Fetch voided bills per day (based on deleted_at)
        $voidedBills = Bill::onlyTrashed()
            ->whereBetween('deleted_at', [$startDate, $endDate])
            ->selectRaw('DATE(deleted_at) as date, count(*) as count')
            ->groupByRaw('DATE(deleted_at)')
            ->pluck('count', 'date');

        // Populate the stats
        foreach ($createdBills as $date => $count) {
            if (isset($dailyStats[$date])) {
                $dailyStats[$date]['bills_created'] = $count;
            }
        }

        foreach ($voidedBills as $date => $count) {
            if (isset($dailyStats[$date])) {
                $dailyStats[$date]['void_bills'] = $count;
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($dailyStats),
        ]);
    }

    /**
     * Get monthly analytics.
     *
     * Retrieve total counts of valid bills (created and active) and voided bills for the specified month and year.
     * Use this data to generate a pie chart.
     *
     * @group Dashboard
     * @authenticated
     * @header Authorization Bearer {token}
     * 
     * @urlParam month integer The month number (1-12). Defaults to current month. Example: 12
     * @urlParam year integer The year. Defaults to current year. Example: 2025
     *
     * @response 200 {
     *     "success": true,
     *     "data": {
     *         "total_bills": 50,
     *         "total_void_bills": 10
     *     }
     * }
     */
    public function monthlyAnalytic($month = null, $year = null)
    {
        $month = $month ?: Carbon::now()->month;
        $year = $year ?: Carbon::now()->year;
        $totalBills = Bill::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $totalVoidBills = Bill::onlyTrashed()
            ->whereMonth('deleted_at', $month)
            ->whereYear('deleted_at', $year)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_bills' => $totalBills,
                'total_void_bills' => $totalVoidBills,
            ],
        ]);
    }
}
