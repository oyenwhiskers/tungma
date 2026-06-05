<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Company;
use App\Models\CourierPolicy;
use App\Models\User;
use App\Models\BusDepartures;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class BillController extends Controller
{
    /**
     * Generate the next bill code for a company
     *
     * @param int $companyId
     * @return string
     */
    public function calculateSst($subtotal, $rate)
    {
        return round($subtotal * ($rate / 100), 2);
    }

    /**
     * Generate the next bill code for a company
     *
     * @param int $companyId
     * @return string
     */
    private function generateNextBillCode($companyId)
    {
        $company = Company::findOrFail($companyId);

        if (empty($company->bill_id_prefix)) {
            throw new \Exception('Company does not have a bill ID prefix set. Please set a prefix in company settings.');
        }

        $prefix = $company->bill_id_prefix;

        // Find the latest bill for this company that matches the new format
        $latestBill = Bill::withTrashed()->where('company_id', $companyId)
            ->where('bill_code', 'like', $prefix . '-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextAlphabet = 'A';
        $nextNumber = 1;

        if ($latestBill && !empty($latestBill->bill_code)) {
            $latestCode = $latestBill->bill_code;
            $expectedPrefix = $prefix . '-';

            // Remove the prefix from the beginning of the code
            if (str_starts_with($latestCode, $expectedPrefix)) {
                $suffix = substr($latestCode, strlen($expectedPrefix));
                // Extract alphabet and numeric parts
                if (preg_match('/^([A-Z]+)(\d+)$/', $suffix, $matches)) {
                    $alphabet = $matches[1];
                    $number = (int) $matches[2];

                    if ($number < 99999) {
                        $nextNumber = $number + 1;
                        $nextAlphabet = $alphabet;
                    } else {
                        $nextNumber = 1;
                        $nextAlphabet = $alphabet;
                        $nextAlphabet++; // Rolls over A->B, Z->AA, ZZ->AAA
                    }
                }
            }
        }

        // Pad to minimum 5 digits
        $paddedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        return $prefix . '-' . $nextAlphabet . $paddedNumber;
    }
    public function index(Request $request)
    {
        $user = auth()->user();

        // Start building query
        $query = Bill::query();

        // Apply company filter for admin
        if ($user->role === 'admin') {
            $query->where('company_id', $user->company_id);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'paid') {
                $query->where('is_paid', true);
            } elseif ($request->payment_status === 'unpaid') {
                $query->where('is_paid', false);
            }
        }

        // Filter by collected status
        if ($request->filled('collected_status')) {
            if ($request->collected_status === 'collected') {
                $query->where('is_collected', true);
            } elseif ($request->collected_status === 'uncollected') {
                $query->where('is_collected', false);
            }
        }

        // Filter by company (for super admin)
        if ($request->filled('company_id') && $user->role !== 'admin') {
            $query->where('company_id', $request->company_id);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_details->method', $request->payment_method);
        }

        // Get companies for filter dropdown (only for super admin)
        if ($user->role === 'admin') {
            $companies = Company::where('id', $user->company_id)->get();
        } else {
            $companies = Company::all();
        }

        // Eager load relationships to avoid N+1 queries
        $bills = $query->with(['company', 'checker', 'creator', 'fromCompany', 'toCompany', 'busDeparture'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('bills.index', compact('bills', 'companies'));
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'bill_ids' => 'required|array',
            'bill_ids.*' => 'exists:bills,id',
            'bulk_action' => 'required|string|in:mark_paid,mark_unpaid,mark_collected,mark_uncollected,delete'
        ]);

        $user = auth()->user();
        $query = Bill::whereIn('id', $request->bill_ids);

        // Ensure admin only modifies their company's bills
        if ($user->role === 'admin') {
            $query->where('company_id', $user->company_id);
        }

        $action = $request->bulk_action;
        $count = $query->count();

        if ($action === 'mark_paid') {
            $query->update(['is_paid' => true]);
            $message = "$count bills marked as Paid.";
        } elseif ($action === 'mark_unpaid') {
            $query->update(['is_paid' => false]);
            $message = "$count bills marked as Unpaid.";
        } elseif ($action === 'mark_collected') {
            $query->update(['is_collected' => true, 'status' => 'Collected']);
            $message = "$count bills marked as Collected.";
        } elseif ($action === 'mark_uncollected') {
            // Revert status to Arrived if there's a checker, else In_transit
            $query->each(function($bill) {
                $bill->update([
                    'is_collected' => false,
                    'status' => $bill->checked_by ? 'Arrived' : 'In_transit'
                ]);
            });
            $message = "$count bills marked as Uncollected.";
        } elseif ($action === 'delete') {
            // Must retrieve to trigger soft deletes properly or mass delete
            $query->delete();
            $message = "$count bills deleted successfully.";
        }

        return redirect()->route('bills.index')->with('success', $message);
    }

    public function create()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            // Admin can only see their own company and its policies
            $companies = Company::all();
            $policies = CourierPolicy::all();
            $users = User::all();

            // Admin should only see bus departures belonging to their company
            $busDepartures = BusDepartures::where('company_id', $user->company_id)->get();
        } else {
            // Super admin can see all companies and policies
            $companies = Company::all();
            $policies = CourierPolicy::all();
            $users = User::all();

            // Super admin can see all bus departures with company for display
            $busDepartures = BusDepartures::with('company')->get();
        }

        // Load Customers and Receivers
        if ($user->role === 'admin') {
            $customers = \App\Models\Customer::where('company_id', $user->company_id)->orderBy('name')->get();
            $receivers = \App\Models\Receiver::where('company_id', $user->company_id)->orderBy('name')->get();
        } else {
            $customers = \App\Models\Customer::orderBy('name')->get();
            $receivers = \App\Models\Receiver::orderBy('name')->get();
        }

        return view('bills.create', compact('companies', 'policies', 'users', 'busDepartures', 'customers', 'receivers'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // For regular admin, automatically use their company_id
        // For super admin, allow company_id selection
        $companyIdRule = 'required|exists:companies,id';
        if ($user->role === 'admin') {
            // Admin must use their own company
            $request->merge(['company_id' => $user->company_id]);
        }

        $data = $request->validate([
            'date' => 'required|date',
            'bus_departures_id' => 'nullable|exists:bus_departures,id',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'from_company_id' => 'nullable|exists:companies,id',
            'to_company_id' => 'nullable|exists:companies,id',
            'sender_name' => 'nullable|string',
            'sender_phone' => 'nullable|string',
            'receiver_name' => 'nullable|string',
            'receiver_phone' => 'nullable|string',
            'courier_policy_id' => [
                'nullable',
                Rule::exists('courier_policies', 'id')->where(function ($q) use ($request) {
                    return $q->where('company_id', $request->company_id);
                })
            ],
            'company_id' => $companyIdRule,
            'sst_rate' => 'nullable|numeric',
            'sst_amount' => 'nullable|numeric',
            'media_attachment' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // Max 5MB
            'payment_proof_attachment' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120',
            'is_paid' => 'nullable|boolean',
            'is_collected' => 'nullable|boolean',
        ]);

        // Decode description JSON to check item count
        if ($request->filled('description')) {
            $descArr = json_decode($request->description, true);
            if (is_array($descArr) && count($descArr) > 4) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['description' => 'Maximum of 4 items allowed per bill. Please create a new bill for additional items.']);
            }
        }

        // Auto-generate bill code using company prefix and running number
        try {
            $data['bill_code'] = $this->generateNextBillCode($data['company_id']);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['company_id' => $e->getMessage()]);
        }

        // Ensure bill_code is unique (in case of race condition)
        $company = Company::find($data['company_id']);
        $prefix = $company->bill_id_prefix;

        // Ensure bill_code is unique (in case of race condition)
        while (Bill::where('bill_code', $data['bill_code'])->exists()) {
            $latestCode = $data['bill_code'];
            $expectedPrefix = $prefix . '-';

            if (str_starts_with($latestCode, $expectedPrefix)) {
                $suffix = substr($latestCode, strlen($expectedPrefix));
                if (preg_match('/^([A-Z]+)(\d+)$/', $suffix, $matches)) {
                    $alphabet = $matches[1];
                    $number = (int) $matches[2];

                    if ($number < 99999) {
                        $nextNumber = $number + 1;
                        $nextAlphabet = $alphabet;
                    } else {
                        $nextNumber = 1;
                        $nextAlphabet = $alphabet;
                        $nextAlphabet++;
                    }
                    $paddedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                    $data['bill_code'] = $prefix . '-' . $nextAlphabet . $paddedNumber;
                } else {
                    $data['bill_code'] .= '_dup';
                }
            } else {
                $data['bill_code'] .= '_dup';
            }
        }

        // Build payment_details JSON
        if ($request->payment_method || $request->payment_date) {
            $data['payment_details'] = [
                'method' => $request->payment_method,
                'date' => $request->payment_date,
            ];
        }

        // Build sst_details JSON
        if ($request->sst_rate || $request->sst_amount) {
            $data['sst_details'] = [
                'rate' => $request->sst_rate,
                'amount' => $request->sst_amount,
            ];
        }

        // Auto-select company's policy if not provided
        if (empty($data['courier_policy_id']) && $request->company_id) {
            $autoPolicy = CourierPolicy::where('company_id', $request->company_id)->orderBy('id')->first();
            if ($autoPolicy) {
                $data['courier_policy_id'] = $autoPolicy->id;
            }
        }

        // Snapshot policy into bill
        if (!empty($data['courier_policy_id'])) {
            $policy = CourierPolicy::find($data['courier_policy_id']);
            if ($policy) {
                $data['policy_snapshot'] = [
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'company_id' => $policy->company_id,
                    'company_name' => optional($policy->company)->name,
                ];
            }
        }

        // Handle media attachment upload
        if ($request->hasFile('media_attachment')) {
            $file = $request->file('media_attachment');
            // Sanitize filename
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('bills', $filename, 'public');
            $data['media_attachment'] = $path;
        }

        // Handle payment proof attachment upload
        if ($request->hasFile('payment_proof_attachment')) {
            $file = $request->file('payment_proof_attachment');
            $filename = time() . '_proof_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('bills', $filename, 'public');
            $data['payment_proof_attachment'] = $path;
        }

        // Set created_by to current authenticated user
        $data['created_by'] = auth()->id();
        // Handle is_paid (convert string to boolean if needed)
        if (isset($data['is_paid'])) {
            $data['is_paid'] = filter_var($data['is_paid'], FILTER_VALIDATE_BOOLEAN);
        } else {
            $data['is_paid'] = false;
        }

        // Handle is_collected (convert string to boolean if needed)
        if (isset($data['is_collected'])) {
            $data['is_collected'] = filter_var($data['is_collected'], FILTER_VALIDATE_BOOLEAN);
        } else {
            $data['is_collected'] = false;
        }

        // Set initial status
        if ($data['is_collected']) {
            $data['status'] = 'Collected';
        } elseif (!empty($data['checked_by'])) {
            $data['status'] = 'Arrived';
        } else {
            $data['status'] = 'In_transit';
        }

        // Handle bus_departures_id - allow null (empty string becomes null)
        if (isset($data['bus_departures_id']) && $data['bus_departures_id'] === '') {
            $data['bus_departures_id'] = null;
        }

        try {
            $bill = Bill::create($data);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create bill. ' . $e->getMessage());
        }

        // Dispatch PDF generation job (Async)
        \App\Jobs\GenerateBillPdf::dispatch($bill);
        
        return redirect()->route('bills.index')->with('success', 'Bill created successfully');
    }

    public function show(Bill $bill)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $bill->company_id) {
            abort(403, 'You can only view bills from your company');
        }
        $bill->load('fromCompany', 'toCompany', 'busDeparture', 'creator', 'checker');
        return view('bills.show', compact('bill'));
    }

    public function edit(Bill $bill)
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            // Admin can only see their own company and its policies
            $companies = Company::all();
            $policies = CourierPolicy::where('company_id', $user->company_id)->get();
            $users = User::where('company_id', $user->company_id)->get();

            // Admin should only see bus departures belonging to their company
            $busDepartures = BusDepartures::where('company_id', $user->company_id)->get();
        } else {
            // Super admin can see all companies and policies
            $companies = Company::all();
            $policies = CourierPolicy::all();
            $users = User::all();

            // Super admin can see all bus departures with company for display
            $busDepartures = BusDepartures::with('company')->get();
        }

        // Load Customers and Receivers
        if ($user->role === 'admin') {
            $customers = \App\Models\Customer::where('company_id', $user->company_id)->orderBy('name')->get();
            $receivers = \App\Models\Receiver::where('company_id', $user->company_id)->orderBy('name')->get();
        } else {
            $customers = \App\Models\Customer::orderBy('name')->get();
            $receivers = \App\Models\Receiver::orderBy('name')->get();
        }

        return view('bills.edit', compact('bill', 'companies', 'policies', 'users', 'busDepartures', 'customers', 'receivers'));
    }

    public function update(Request $request, Bill $bill)
    {
        $user = auth()->user();

        // For regular admin, automatically use their company_id
        // For super admin, allow company_id selection
        if ($user->role === 'admin') {
            // Admin must use their own company
            $request->merge(['company_id' => $user->company_id]);
        }

        $data = $request->validate([
            'bill_code' => 'required|string|max:255|unique:bills,bill_code,' . $bill->id,
            'date' => 'required|date',
            'bus_departures_id' => 'nullable|exists:bus_departures,id',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'from_company_id' => 'nullable|exists:companies,id',
            'to_company_id' => 'nullable|exists:companies,id',
            'sender_name' => 'nullable|string',
            'sender_phone' => 'nullable|string',
            'receiver_name' => 'nullable|string',
            'receiver_phone' => 'nullable|string',
            'courier_policy_id' => [
                'nullable',
                Rule::exists('courier_policies', 'id')->where(function ($q) use ($request) {
                    return $q->where('company_id', $request->company_id);
                })
            ],
            'company_id' => 'required|exists:companies,id',
            'sst_rate' => 'nullable|numeric',
            'sst_amount' => 'nullable|numeric',
            'media_attachment' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // Max 5MB
            'payment_proof_attachment' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120',
            'is_paid' => 'nullable|boolean',
            'is_collected' => 'nullable|boolean',
        ]);

        // Decode description JSON to check item count
        if ($request->filled('description')) {
            $descArr = json_decode($request->description, true);
            if (is_array($descArr) && count($descArr) > 4) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['description' => 'Maximum of 4 items allowed per bill. Please create a new bill for additional items.']);
            }
        }

        // Build payment_details JSON
        if ($request->payment_method || $request->payment_date) {
            $data['payment_details'] = [
                'method' => $request->payment_method,
                'date' => $request->payment_date,
            ];
        }

        // Build sst_details JSON
        if ($request->sst_rate || $request->sst_amount) {
            $data['sst_details'] = [
                'rate' => $request->sst_rate,
                'amount' => $request->sst_amount,
            ];
        }

        // If company changed and policy no longer matches, auto-adjust
        if (!empty($data['courier_policy_id'])) {
            $policy = CourierPolicy::find($data['courier_policy_id']);
            if (!$policy || $policy->company_id != $data['company_id']) {
                $data['courier_policy_id'] = null;
            }
        }
        if (empty($data['courier_policy_id']) && $data['company_id']) {
            $autoPolicy = CourierPolicy::where('company_id', $data['company_id'])->orderBy('id')->first();
            if ($autoPolicy) {
                $data['courier_policy_id'] = $autoPolicy->id;
            }
        }

        // Refresh snapshot when policy or company changed
        if (!empty($data['courier_policy_id'])) {
            $policy = CourierPolicy::find($data['courier_policy_id']);
            if ($policy) {
                $data['policy_snapshot'] = [
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'company_id' => $policy->company_id,
                    'company_name' => optional($policy->company)->name,
                ];
            }
        } else {
            $data['policy_snapshot'] = null;
        }

        // Handle media attachment upload
        if ($request->hasFile('media_attachment')) {
            // Delete old attachment if exists
            if ($bill->media_attachment && Storage::disk('public')->exists($bill->media_attachment)) {
                Storage::disk('public')->delete($bill->media_attachment);
            }

            $file = $request->file('media_attachment');
            // Sanitize filename
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('bills', $filename, 'public');
            $data['media_attachment'] = $path;
        }

        // Handle payment proof attachment upload
        if ($request->hasFile('payment_proof_attachment')) {
            if ($bill->payment_proof_attachment && Storage::disk('public')->exists($bill->payment_proof_attachment)) {
                Storage::disk('public')->delete($bill->payment_proof_attachment);
            }

            $file = $request->file('payment_proof_attachment');
            $filename = time() . '_proof_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('bills', $filename, 'public');
            $data['payment_proof_attachment'] = $path;
        }

        // Handle is_paid (convert string to boolean if needed)
        if (isset($data['is_paid'])) {
            $data['is_paid'] = filter_var($data['is_paid'], FILTER_VALIDATE_BOOLEAN);
        }

        // Handle is_collected (convert string to boolean if needed)
        if (isset($data['is_collected'])) {
            $data['is_collected'] = filter_var($data['is_collected'], FILTER_VALIDATE_BOOLEAN);
        }

        // Handle checked_by - allow null (empty string becomes null)
        if (isset($data['checked_by']) && $data['checked_by'] === '') {
            $data['checked_by'] = null;
        }

        // Handle bus_departures_id - allow null (empty string becomes null)
        if (isset($data['bus_departures_id']) && $data['bus_departures_id'] === '') {
            $data['bus_departures_id'] = null;
        }

        // Set status
        $isCollected = isset($data['is_collected']) ? filter_var($data['is_collected'], FILTER_VALIDATE_BOOLEAN) : $bill->is_collected;
        $hasChecker = !empty($data['checked_by']) || (!array_key_exists('checked_by', $data) && $bill->checked_by);

        if ($isCollected) {
            $data['status'] = 'Collected';
        } elseif ($hasChecker) {
            $data['status'] = 'Arrived';
        } else {
            $data['status'] = 'In_transit';
        }

        try {
            $bill->update($data);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update bill. ' . $e->getMessage());
        }

        // Dispatch PDF generation job (Async)
        \App\Jobs\GenerateBillPdf::dispatch($bill);

        return redirect()->route('bills.show', $bill)->with('success', 'Bill updated successfully');
    }

    public function destroy(Bill $bill)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $bill->company_id) {
            abort(403, 'You can only delete bills from your company');
        }
        $bill->delete();
        return redirect()->route('bills.index');
    }

    public function deleted()
    {
        $user = auth()->user();
        if ($user->role === 'admin') {
            $bills = Bill::onlyTrashed()->where('company_id', $user->company_id)->paginate(20);
        } else {
            $bills = Bill::onlyTrashed()->paginate(20);
        }
        return view('bills.deleted', compact('bills'));
    }

    public function restore($id)
    {
        $bill = Bill::onlyTrashed()->findOrFail($id);
        $bill->restore();
        return redirect()->route('bills.index')->with('status', 'Bill restored');
    }

    public function template(Bill $bill, Request $request)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $bill->company_id) {
            abort(403, 'You can only view bills from your company');
        }

        // Load relationships
        $bill->load('company', 'courierPolicy', 'fromCompany', 'toCompany');

        // Parse JSON fields
        $sstDetails = null;
        if ($bill->sst_details) {
            $sstDetails = is_string($bill->sst_details) ? json_decode($bill->sst_details, true) : $bill->sst_details;
        }

        $paymentDetails = null;
        if ($bill->payment_details) {
            $paymentDetails = is_string($bill->payment_details) ? json_decode($bill->payment_details, true) : $bill->payment_details;
        }

        // Get copy type (customer, office, receiver, or book)
        $copyType = $request->get('copy', 'customer'); // Default to customer copy
        $validCopyTypes = ['customer', 'office', 'receiver', 'book'];
        if (!in_array($copyType, $validCopyTypes)) {
            $copyType = 'customer';
        }

        // Determine which template to use
        $templateView = ($copyType === 'office' || $copyType === 'receiver')
            ? 'bills.template-office'
            : 'bills.template';

        // Generate PDF
        $pdf = \PDF::loadView($templateView, compact('bill', 'sstDetails', 'paymentDetails', 'copyType') + ['isPdf' => true])
            ->setPaper('a4', 'portrait');

        // If download parameter is set, download the PDF
        if ($request->has('download')) {
            return $pdf->download('bill-' . $bill->bill_code . '-' . $copyType . '.pdf');
        }

        // Otherwise, return PDF for viewing
        return $pdf->stream('bill-' . $bill->bill_code . '-' . $copyType . '.pdf');
    }

    public function viewTemplate(Bill $bill)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $bill->company_id) {
            abort(403, 'You can only view bills from your company');
        }

        $bill->load('company', 'courierPolicy', 'fromCompany', 'toCompany');
        return view('bills.view-template', compact('bill'));
    }
}
