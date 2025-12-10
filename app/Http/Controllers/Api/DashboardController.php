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
}
