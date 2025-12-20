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
    private function generateNextBillCode($companyId)
    {
        $company = Company::findOrFail($companyId);

        if (empty($company->bill_id_prefix)) {
            throw new \Exception('Company does not have a bill ID prefix set. Please set a prefix in company settings.');
        }

        // Find the latest bill for this company
        $latestBill = Bill::withTrashed()->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;

        if ($latestBill && !empty($latestBill->bill_code)) {
            // Extract the number part from the latest bill code
            $prefix = $company->bill_id_prefix;
            $latestCode = $latestBill->bill_code;

            // Remove the prefix from the beginning of the code
            if (str_starts_with($latestCode, $prefix)) {
                $numberPart = substr($latestCode, strlen($prefix));
                // Extract numeric part (handle cases where there might be non-numeric characters)
                if (preg_match('/^(\d+)/', $numberPart, $matches)) {
                    $nextNumber = (int)$matches[1] + 1;
                }
            }
        }

        // Pad to minimum 6 digits
        $paddedNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        return $company->bill_id_prefix . $paddedNumber;
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
            $query->where(function($q) use ($search) {
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

    public function create()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            // Admin can only see their own company and its policies
            $companies = Company::where('id', $user->company_id)->get();
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

        return view('bills.create', compact('companies', 'policies', 'users', 'busDepartures'));
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
                Rule::exists('courier_policies', 'id')->where(function($q) use ($request) {
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
            'checked_by' => 'nullable|exists:users,id',
        ]);

        // Auto-generate bill code using company prefix and running number
        try {
            $data['bill_code'] = $this->generateNextBillCode($data['company_id']);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['company_id' => $e->getMessage()]);
        }

        // Ensure bill_code is unique (in case of race condition)
        while (Bill::where('bill_code', $data['bill_code'])->exists()) {
            // If collision occurs, increment and try again
            $company = Company::find($data['company_id']);
            $latestBill = Bill::where('company_id', $data['company_id'])
                ->orderBy('id', 'desc')
                ->first();

            $prefix = $company->bill_id_prefix;
            $numberPart = substr($latestBill->bill_code, strlen($prefix));
            if (preg_match('/^(\d+)/', $numberPart, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
                $paddedNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
                $data['bill_code'] = $prefix . $paddedNumber;
            } else {
                break; // Safety break
            }
        }

        // Build payment_details JSON
        if ($request->payment_method || $request->payment_date) {
            $data['payment_details'] = json_encode([
                'method' => $request->payment_method,
                'date' => $request->payment_date,
            ]);
        }

        // Build sst_details JSON
        if ($request->sst_rate || $request->sst_amount) {
            $data['sst_details'] = json_encode([
                'rate' => $request->sst_rate,
                'amount' => $request->sst_amount,
            ]);
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
                $data['policy_snapshot'] = json_encode([
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'company_id' => $policy->company_id,
                    'company_name' => optional($policy->company)->name,
                ]);
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
        $data['status'] = 'In_transit';

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

        // Handle bus_departures_id - allow null (empty string becomes null)
        if (isset($data['bus_departures_id']) && $data['bus_departures_id'] === '') {
            $data['bus_departures_id'] = null;
        }

        Bill::create($data);
        return redirect()->route('bills.index')->with('success', 'Bill created successfully');
    }

    public function show(Bill $bill)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $bill->company_id) {
            abort(403, 'You can only view bills from your company');
        }
        $bill->load('fromCompany', 'toCompany', 'busDeparture');
        return view('bills.show', compact('bill'));
    }

    public function edit(Bill $bill)
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            // Admin can only see their own company and its policies
            $companies = Company::where('id', $user->company_id)->get();
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

        return view('bills.edit', compact('bill', 'companies', 'policies', 'users', 'busDepartures'));
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
                Rule::exists('courier_policies', 'id')->where(function($q) use ($request) {
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
            'checked_by' => 'nullable|exists:users,id',
        ]);

        // Build payment_details JSON
        if ($request->payment_method || $request->payment_date) {
            $data['payment_details'] = json_encode([
                'method' => $request->payment_method,
                'date' => $request->payment_date,
            ]);
        }

        // Build sst_details JSON
        if ($request->sst_rate || $request->sst_amount) {
            $data['sst_details'] = json_encode([
                'rate' => $request->sst_rate,
                'amount' => $request->sst_amount,
            ]);
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
                $data['policy_snapshot'] = json_encode([
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'company_id' => $policy->company_id,
                    'company_name' => optional($policy->company)->name,
                ]);
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

        // Set status based on whether the bill has been checked
        $checkedByValue = array_key_exists('checked_by', $data) ? $data['checked_by'] : $bill->checked_by;
        $data['status'] = $checkedByValue ? 'Arrived' : 'In_transit';

        $bill->update($data);
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
        $pdf = \PDF::loadView($templateView, compact('bill', 'sstDetails', 'paymentDetails', 'copyType'))
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
