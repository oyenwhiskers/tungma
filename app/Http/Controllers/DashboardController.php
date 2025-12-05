<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
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

        return view('dashboard', compact(
            'companies_count',
            'admins_count',
            'staff_count',
            'total_revenue',
            'bills_count',
            'active_users'
        ));
    }
}
