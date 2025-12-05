<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillController extends Controller
{
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
        $companies = \App\Models\Company::all();
        $policies = \App\Models\CourierPolicy::all();
        return view('bills.create', compact('companies', 'policies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bill_code' => 'required|string|max:255|unique:bills,bill_code',
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
        $companies = \App\Models\Company::all();
        $policies = \App\Models\CourierPolicy::all();
        return view('bills.edit', compact('bill', 'companies', 'policies'));
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
