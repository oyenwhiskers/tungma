<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $user = auth()->user();

        if ($user->role !== 'super_admin') {
            $billQuery->where('company_id', $user->company_id);
        }

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
        $yearsQuery = Bill::query();
        if ($user->role !== 'super_admin') {
            $yearsQuery->where('company_id', $user->company_id);
        }

        $years = $yearsQuery->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Total revenue (filtered)
        $totalRevenue = (clone $billQuery)->sum('amount');

        // Staff distribution per company
        // Note: Staff is usually a current state, so we might not want to filter by bill dates here.
        // Keeping it as is for now.
        $staffQuery = User::query()
            ->select('company_id', DB::raw('count(*) as total'))
            ->where('role', 'staff');

        if ($user->role !== 'super_admin') {
            $staffQuery->where('company_id', $user->company_id);
        }

        $staffDistribution = $staffQuery
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

        $showStaffBreakdown = in_array($user->role, ['admin', 'super_admin']);
        $staffStats = [];
        $search_staff = $request->input('search_staff');
        $selected_company_id = $request->input('company_id');
        $companies = [];
        
        $staff_total_cash = 0.0;
        $staff_total_cod = 0.0;
        $staff_total_qr = 0.0;
        $staff_total_transfer = 0.0;
        $staff_total_voids = 0;
        $staff_total_sales = 0.0;
        $staff_total_bills = 0;
        $staffChartData = [];
        $staffPaymentMethodsChartData = [];


        if ($showStaffBreakdown) {
            // 1. Get all staff
            $staffQuery = User::where('role', 'staff');
            
            // 2. Fetch active and voided bills queries using page's main filters (selectedYear, selectedMonth, selectedDay)
            $activeBillsQuery = Bill::query();
            $voidedBillsQuery = Bill::onlyTrashed();

            if ($user->role === 'super_admin') {
                $companies = Company::orderBy('name')->get();
                if ($selected_company_id) {
                    $staffQuery->where('company_id', $selected_company_id);
                    $activeBillsQuery->where('company_id', $selected_company_id);
                    $voidedBillsQuery->where('company_id', $selected_company_id);
                }
            } else {
                $staffQuery->where('company_id', $user->company_id);
                $activeBillsQuery->where('company_id', $user->company_id);
                $voidedBillsQuery->where('company_id', $user->company_id);
            }

            if ($selectedYear) {
                $activeBillsQuery->whereYear('created_at', $selectedYear);
                $voidedBillsQuery->whereYear('deleted_at', $selectedYear);
            }
            if ($selectedMonth) {
                $activeBillsQuery->whereMonth('created_at', $selectedMonth);
                $voidedBillsQuery->whereMonth('deleted_at', $selectedMonth);
            }
            if ($selectedDay) {
                $activeBillsQuery->whereDay('created_at', $selectedDay);
                $voidedBillsQuery->whereDay('deleted_at', $selectedDay);
            }

            if ($search_staff) {
                $staffQuery->where('name', 'like', '%' . $search_staff . '%');
            }
            $staffMembers = $staffQuery->get();

            $activeBills = $activeBillsQuery->with(['fromCompany', 'toCompany', 'busDeparture', 'creator'])->get();
            $voidedBills = $voidedBillsQuery->get(['id', 'created_by']);

            // Group bills by staff id to calculate totals
            foreach ($staffMembers as $member) {
                $memberBills = $activeBills->where('created_by', $member->id);
                $memberVoidedCount = $voidedBills->where('created_by', $member->id)->count();

                $cashSales = 0.0;
                $codSales = 0.0;
                $qrSales = 0.0;
                $transferSales = 0.0;
                $totalSales = 0.0;

                foreach ($memberBills as $bill) {
                    $amount = (float)$bill->amount;
                    $totalSales += $amount;

                    $details = $bill->payment_details;
                    if (is_string($details)) {
                        $details = json_decode($details, true);
                    }

                    $method = strtolower($details['method'] ?? '');

                    if ($method === 'cash') {
                        $cashSales += $amount;
                    } elseif ($method === 'cod') {
                        $codSales += $amount;
                    } elseif (in_array($method, ['qr', 'e_wallet', 'qr_pay', 'e_wallet_qr'])) {
                        $qrSales += $amount;
                    } elseif (in_array($method, ['bank_transfer', 'bank'])) {
                        $transferSales += $amount;
                    }
                }

                $staffStats[] = [
                    'staff' => $member,
                    'cash' => $cashSales,
                    'cod' => $codSales,
                    'qr' => $qrSales,
                    'transfer' => $transferSales,
                    'void_count' => $memberVoidedCount,
                    'total_sales' => $totalSales,
                    'total_bills' => $memberBills->count(),
                    'bills' => $memberBills,
                ];
            }

            // Calculate totals across all staff
            foreach ($staffStats as $stat) {
                $staff_total_cash += $stat['cash'];
                $staff_total_cod += $stat['cod'];
                $staff_total_qr += $stat['qr'];
                $staff_total_transfer += $stat['transfer'];
                $staff_total_voids += $stat['void_count'];
                $staff_total_sales += $stat['total_sales'];
                $staff_total_bills += $stat['total_bills'];

                if ($stat['total_sales'] > 0) {
                    $staffChartData[] = [
                        'name' => $stat['staff']->name,
                        'sales' => $stat['total_sales'],
                    ];
                }

                $staffPaymentMethodsChartData[] = [
                    'name' => $stat['staff']->name,
                    'cash' => (float)$stat['cash'],
                    'cod' => (float)$stat['cod'],
                    'qr' => (float)$stat['qr'],
                    'transfer' => (float)$stat['transfer']
                ];
            }
        }

        return view('analytics.index', compact(
            'totalRevenue',
            'staffDistribution',
            'billSummaries',
            'revenueTrend',
            'filter',
            'years',
            'selectedYear',
            'selectedMonth',
            'selectedDay',
            'showStaffBreakdown',
            'staffStats',
            'search_staff',
            'companies',
            'selected_company_id',
            'staff_total_cash',
            'staff_total_cod',
            'staff_total_qr',
            'staff_total_transfer',
            'staff_total_voids',
            'staff_total_sales',
            'staff_total_bills',
            'staffChartData',
            'staffPaymentMethodsChartData'
        ));
    }
}
