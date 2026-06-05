<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'super_admin') {
            // Super Admin: Show summaries of all companies
            $companies_count = Company::count();
            $admins_count = User::where('role', 'admin')->count();
            $staff_count = User::where('role', 'staff')->count();
            $total_revenue = Bill::sum('amount');
            $bills_count = Bill::count();
            $active_users = User::whereNull('deleted_at')->count();
        } else {
            // Admin: Show summaries only for their company
            $company = $user->company;
            $companies_count = 1; // Their own company
            $admins_count = 0; // Admins don't manage other admins
            $staff_count = User::where('role', 'staff')
                ->where('company_id', $user->company_id)
                ->count();
            $total_revenue = Bill::where('company_id', $user->company_id)->sum('amount');
            $bills_count = Bill::where('company_id', $user->company_id)->count();
            $active_users = User::whereNull('deleted_at')
                ->where('company_id', $user->company_id)
                ->count();
        }

        $showStaffBreakdown = in_array($user->role, ['admin', 'super_admin']);
        $staffStats = [];
        $start_date = null;
        $end_date = null;
        $search_staff = null;
        $companies = [];
        $selected_company_id = null;
        $staff_total_cash = 0.0;
        $staff_total_cod = 0.0;
        $staff_total_qr = 0.0;
        $staff_total_transfer = 0.0;
        $staff_total_voids = 0;
        $staff_total_sales = 0.0;
        $staff_total_bills = 0;

        if ($showStaffBreakdown) {
            $start_date = $request->filled('start_date') ? $request->input('start_date') : Carbon::today()->toDateString();
            $end_date = $request->filled('end_date') ? $request->input('end_date') : Carbon::today()->toDateString();
            $search_staff = $request->input('search_staff');

            $startDate = Carbon::parse($start_date)->startOfDay();
            $endDate = Carbon::parse($end_date)->endOfDay();

            // 1. Get all staff
            $staffQuery = User::where('role', 'staff');
            
            // 2. Fetch active and voided bills base queries
            $activeBillsQuery = Bill::whereBetween('date', [$startDate, $endDate]);
            $voidedBillsQuery = Bill::onlyTrashed()->whereBetween('deleted_at', [$startDate, $endDate]);

            if ($user->role === 'super_admin') {
                $companies = Company::orderBy('name')->get();
                $selected_company_id = $request->input('company_id');
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
            }
        }

        return view('dashboard', compact(
            'companies_count',
            'admins_count',
            'staff_count',
            'total_revenue',
            'bills_count',
            'active_users',
            'showStaffBreakdown',
            'staffStats',
            'start_date',
            'end_date',
            'search_staff',
            'companies',
            'selected_company_id',
            'staff_total_cash',
            'staff_total_cod',
            'staff_total_qr',
            'staff_total_transfer',
            'staff_total_voids',
            'staff_total_sales',
            'staff_total_bills'
        ));
    }
}
