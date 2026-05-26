<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
    public function index(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today();
        $now   = Carbon::now();

        // ── 1. My Today's Summary (current user only) ──────────────────────
        $myBillsToday = Bill::whereDate('date', $today)
            ->where('created_by', $user->id)
            ->get(['amount', 'payment_details']);

        $cashAmount = 0;
        $bankTransferAmount = 0;
        $codAmount = 0;
        $eWalletAmount = 0;

        foreach ($myBillsToday as $bill) {
            $details = $bill->payment_details;
            if (is_string($details)) {
                $details = json_decode($details, true);
            }
            
            $method = strtolower($details['method'] ?? '');
            $amount = (float) $bill->amount;

            if ($method === 'cash') {
                $cashAmount += $amount;
            } elseif ($method === 'bank_transfer') {
                $bankTransferAmount += $amount;
            } elseif ($method === 'cod') {
                $codAmount += $amount;
            } elseif ($method === 'e_wallet') {
                $eWalletAmount += $amount;
            }
        }

        $data = [
            'my_today' => [
                'bill_count'   => $myBillsToday->count(),
                'total_amount' => (float) ($cashAmount + $bankTransferAmount + $eWalletAmount),
                'cash_amount'  => $cashAmount,
                'bank_transfer_amount' => $bankTransferAmount,
                'cod_amount'   => $codAmount,
                'e_wallet_amount' => $eWalletAmount,
                'date'         => $today->toDateString(),
            ],
            'role' => $user->role,
        ];

        // ── 2. Company & Global scope (Only for Admin/Super Admin) ──────────
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            $companyId = $user->company_id;

            $companyBillsToday    = Bill::where('company_id', $companyId)->whereDate('created_at', $today)->count();
            $companyVoidToday     = Bill::onlyTrashed()->where('company_id', $companyId)->whereDate('deleted_at', $today)->count();
            $companyBillsMonth    = Bill::where('company_id', $companyId)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
            $companyVoidMonth     = Bill::onlyTrashed()->where('company_id', $companyId)->whereMonth('deleted_at', $now->month)->whereYear('deleted_at', $now->year)->count();
            $companyAmountToday   = (float) Bill::where('company_id', $companyId)->whereDate('created_at', $today)->sum('amount');
            $companyAmountMonth   = (float) Bill::where('company_id', $companyId)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('amount');

            $globalBillsToday  = Bill::whereDate('created_at', $today)->count();
            $globalVoidToday   = Bill::onlyTrashed()->whereDate('deleted_at', $today)->count();
            $globalBillsMonth  = Bill::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
            $globalVoidMonth   = Bill::onlyTrashed()->whereMonth('deleted_at', $now->month)->whereYear('deleted_at', $now->year)->count();
            $globalAmountToday = (float) Bill::whereDate('created_at', $today)->sum('amount');
            $globalAmountMonth = (float) Bill::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('amount');

            $data['company'] = [
                'bills_created_today'        => $companyBillsToday,
                'void_bills_today'            => $companyVoidToday,
                'total_bills_this_month'      => $companyBillsMonth,
                'total_void_bills_this_month' => $companyVoidMonth,
                'amount_today'                => $companyAmountToday,
                'amount_this_month'           => $companyAmountMonth,
            ];

            $data['global'] = [
                'bills_created_today'        => $globalBillsToday,
                'void_bills_today'            => $globalVoidToday,
                'total_bills_this_month'      => $globalBillsMonth,
                'total_void_bills_this_month' => $globalVoidMonth,
                'amount_today'                => $globalAmountToday,
                'amount_this_month'           => $globalAmountMonth,
            ];
        } else {
            $data['company'] = null;
            $data['global'] = null;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
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
     * @queryParam month integer The month number (1-12). Defaults to current month. Example: 12
     * @queryParam year integer The year. Defaults to current year. Example: 2025
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
    public function dailyAnalytic(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
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
     * @queryParam month integer The month number (1-12). Defaults to current month. Example: 12
     * @queryParam year integer The year. Defaults to current year. Example: 2025
     *
     * @response 200 {
     *     "success": true,
     *     "data": {
     *         "total_bills": 50,
     *         "total_void_bills": 10
     *     }
     * }
     */
    public function monthlyAnalytic(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
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

    /**
     * Get personal summary for a specific date.
     *
     * @group Dashboard
     * @authenticated
     * @header Authorization Bearer {token}
     * @queryParam date string The date (Y-m-d). Defaults to today. Example: 2025-12-14
     */
    public function personalSummary(Request $request)
    {
        $user = $request->user();
        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::today();

        $bills = Bill::whereDate('date', $date)
            ->where('created_by', $user->id)
            ->get(['amount', 'payment_details']);

        $cashAmount = 0;
        $bankTransferAmount = 0;
        $codAmount = 0;
        $eWalletAmount = 0;

        foreach ($bills as $bill) {
            $details = $bill->payment_details;
            if (is_string($details)) {
                $details = json_decode($details, true);
            }
            
            $method = strtolower($details['method'] ?? '');
            $amount = (float) $bill->amount;

            if ($method === 'cash') {
                $cashAmount += $amount;
            } elseif ($method === 'bank_transfer') {
                $bankTransferAmount += $amount;
            } elseif ($method === 'cod') {
                $codAmount += $amount;
            } elseif ($method === 'e_wallet') {
                $eWalletAmount += $amount;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'bill_count'   => $bills->count(),
                'total_amount' => (float) ($cashAmount + $bankTransferAmount + $eWalletAmount),
                'cash_amount'  => $cashAmount,
                'bank_transfer_amount' => $bankTransferAmount,
                'cod_amount'   => $codAmount,
                'e_wallet_amount' => $eWalletAmount,
                'date'         => $date->toDateString(),
            ],
        ]);
    }

}
