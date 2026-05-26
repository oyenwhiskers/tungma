<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Customer::query();

        if ($user->role === 'admin') {
            $query->where('company_id', $user->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('debtor_code', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $customers = $query->with('company')->latest()->paginate(15)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        $companies = $user->role === 'super_admin' ? \App\Models\Company::all() : \App\Models\Company::where('id', $user->company_id)->get();
        return view('customers.create', compact('companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'debtor_code' => 'required|string|max:50|unique:customers,debtor_code',
            'company_id' => $user->role === 'super_admin' ? 'required|exists:companies,id' : 'nullable',
        ]);

        if ($user->role === 'admin') {
            $validated['company_id'] = $user->company_id;
        }

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $customer->company_id !== $user->company_id) {
            abort(403);
        }

        $companies = $user->role === 'super_admin' ? \App\Models\Company::all() : \App\Models\Company::where('id', $user->company_id)->get();
        return view('customers.edit', compact('customer', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $customer->company_id !== $user->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'debtor_code' => 'required|string|max:50|unique:customers,debtor_code,' . $customer->id,
            'company_id' => $user->role === 'super_admin' ? 'required|exists:companies,id' : 'nullable',
        ]);

        if ($user->role === 'admin') {
            $validated['company_id'] = $user->company_id;
        }

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully. You can restore it from the deleted customers list.');
    }

    /**
     * Display a listing of soft-deleted resources.
     */
    public function deleted(Request $request)
    {
        $query = Customer::onlyTrashed();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('debtor_code', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest('deleted_at')->paginate(15)->withQueryString();

        return view('customers.deleted', compact('customers'));
    }

    /**
     * Restore the specified soft-deleted resource.
     */
    public function restore($id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        return redirect()->route('customers.deleted')->with('success', 'Customer restored successfully.');
    }

    /**
     * Generate a unique debtor code based on the given name.
     * Starts with '300-', followed by first letter of name, then a 3-digit sequence.
     */
    public function generateCode(Request $request)
    {
        $name = $request->input('name');
        
        if (empty($name)) {
            return response()->json(['code' => '']);
        }

        // Get the first alphanumeric letter
        $firstLetter = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 1));
        
        if (empty($firstLetter)) {
            $firstLetter = 'A'; // Fallback if name has no letters
        }

        $prefix = "300-" . $firstLetter;

        // Find the latest code starting with this prefix
        // We use withTrashed() to ensure we don't reuse codes from soft-deleted customers
        $latestCustomer = Customer::withTrashed()
            ->where('debtor_code', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(debtor_code) DESC') 
            ->orderBy('debtor_code', 'desc')
            ->first();

        if ($latestCustomer) {
            // Extract the number part
            $numberPart = str_replace($prefix, '', $latestCustomer->debtor_code);
            $nextNumber = intval($numberPart) + 1;
        } else {
            $nextNumber = 1;
        }

        // Pad to 3 digits (e.g., 001, 002)
        $newCode = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return response()->json(['code' => $newCode]);
    }
}
