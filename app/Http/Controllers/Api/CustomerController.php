<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Get a list of all registered customers/companies.
     * The mobile app can use this to show a dropdown of existing customers.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Customer::query();

        // If not super admin, only return customers belonging to the user's company
        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
        } else if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->get(['id', 'name', 'contact_number', 'debtor_code', 'company_id']);
        
        return response()->json([
            'status' => 'success',
            'data' => $customers
        ]);
    }
}
