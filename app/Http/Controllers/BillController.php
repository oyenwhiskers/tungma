<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Company;
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
        $latestBill = Bill::where('company_id', $companyId)
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
    public function index()
    {
        $user = auth()->user();
        if ($user->role === 'admin') {
            $bills = Bill::where('company_id', $user->company_id)->latest()->paginate(20);
        } else {
            $bills = Bill::query()->latest()->paginate(20);
        }
        return view('bills.index', compact('bills'));
    }

    public function create()
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            // Admin can only see their own company and its policies
            $companies = \App\Models\Company::where('id', $user->company_id)->get();
            $policies = \App\Models\CourierPolicy::where('company_id', $user->company_id)->get();
            $users = \App\Models\User::where('company_id', $user->company_id)->get();
        } else {
            // Super admin can see all companies and policies
            $companies = \App\Models\Company::all();
            $policies = \App\Models\CourierPolicy::all();
            $users = \App\Models\User::all();
        }
        
        return view('bills.create', compact('companies', 'policies', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'courier_policy_id' => [
                'nullable',
                Rule::exists('courier_policies', 'id')->where(function($q) use ($request) {
                    return $q->where('company_id', $request->company_id);
                })
            ],
            'company_id' => 'required|exists:companies,id',
            'eta' => 'nullable|string',
            'sst_rate' => 'nullable|numeric',
            'sst_amount' => 'nullable|numeric',
            'media_attachment' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // Max 5MB
            'is_paid' => 'nullable|boolean',
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

        // Build customer_info JSON
        if ($request->customer_name || $request->customer_phone || $request->customer_address) {
            $data['customer_info'] = json_encode([
                'name' => $request->customer_name,
                'phone' => $request->customer_phone,
                'address' => $request->customer_address,
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
            $autoPolicy = \App\Models\CourierPolicy::where('company_id', $request->company_id)->orderBy('id')->first();
            if ($autoPolicy) {
                $data['courier_policy_id'] = $autoPolicy->id;
            }
        }

        // Snapshot policy into bill
        if (!empty($data['courier_policy_id'])) {
            $policy = \App\Models\CourierPolicy::find($data['courier_policy_id']);
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

        // Set created_by to current authenticated user
        $data['created_by'] = auth()->id();
        
        // Handle is_paid (convert string to boolean if needed)
        if (isset($data['is_paid'])) {
            $data['is_paid'] = filter_var($data['is_paid'], FILTER_VALIDATE_BOOLEAN);
        } else {
            $data['is_paid'] = false;
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
        return view('bills.show', compact('bill'));
    }

    public function edit(Bill $bill)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            // Admin can only see their own company and its policies
            $companies = \App\Models\Company::where('id', $user->company_id)->get();
            $policies = \App\Models\CourierPolicy::where('company_id', $user->company_id)->get();
            $users = \App\Models\User::where('company_id', $user->company_id)->get();
        } else {
            // Super admin can see all companies and policies
            $companies = \App\Models\Company::all();
            $policies = \App\Models\CourierPolicy::all();
            $users = \App\Models\User::all();
        }
        
        return view('bills.edit', compact('bill', 'companies', 'policies', 'users'));
    }

    public function update(Request $request, Bill $bill)
    {
        $data = $request->validate([
            'bill_code' => 'required|string|max:255|unique:bills,bill_code,' . $bill->id,
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'courier_policy_id' => [
                'nullable',
                Rule::exists('courier_policies', 'id')->where(function($q) use ($request) {
                    return $q->where('company_id', $request->company_id);
                })
            ],
            'company_id' => 'required|exists:companies,id',
            'eta' => 'nullable|string',
            'sst_rate' => 'nullable|numeric',
            'sst_amount' => 'nullable|numeric',
            'media_attachment' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // Max 5MB
            'is_paid' => 'nullable|boolean',
            'checked_by' => 'nullable|exists:users,id',
        ]);

        // Build payment_details JSON
        if ($request->payment_method || $request->payment_date) {
            $data['payment_details'] = json_encode([
                'method' => $request->payment_method,
                'date' => $request->payment_date,
            ]);
        }

        // Build customer_info JSON
        if ($request->customer_name || $request->customer_phone || $request->customer_address) {
            $data['customer_info'] = json_encode([
                'name' => $request->customer_name,
                'phone' => $request->customer_phone,
                'address' => $request->customer_address,
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
            $policy = \App\Models\CourierPolicy::find($data['courier_policy_id']);
            if (!$policy || $policy->company_id != $data['company_id']) {
                $data['courier_policy_id'] = null;
            }
        }
        if (empty($data['courier_policy_id']) && $data['company_id']) {
            $autoPolicy = \App\Models\CourierPolicy::where('company_id', $data['company_id'])->orderBy('id')->first();
            if ($autoPolicy) {
                $data['courier_policy_id'] = $autoPolicy->id;
            }
        }

        // Refresh snapshot when policy or company changed
        if (!empty($data['courier_policy_id'])) {
            $policy = \App\Models\CourierPolicy::find($data['courier_policy_id']);
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

        // Handle is_paid (convert string to boolean if needed)
        if (isset($data['is_paid'])) {
            $data['is_paid'] = filter_var($data['is_paid'], FILTER_VALIDATE_BOOLEAN);
        }

        // Handle checked_by - allow null (empty string becomes null)
        if (isset($data['checked_by']) && $data['checked_by'] === '') {
            $data['checked_by'] = null;
        }

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
}
