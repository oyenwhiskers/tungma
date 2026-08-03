<?php

namespace App\Http\Controllers;

use App\Models\CourierPolicy;
use Illuminate\Http\Request;

class CourierPolicyController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->role === 'admin') {
            $policies = CourierPolicy::where('company_id', $user->company_id)->paginate(20);
        } else {
            $policies = CourierPolicy::query()->paginate(20);
        }
        return view('policies.index', compact('policies'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->role === 'admin') {
            $companies = \App\Models\Company::where('id', $user->company_id)->get();
        } else {
            $companies = \App\Models\Company::all();
        }
        return view('policies.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('courier_policies')->withoutTrashed()->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);
                })
            ],
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ], [
            'name.unique' => 'A policy with this name already exists for the selected company.'
        ]);
        // Force admin to create policies only for their company
        if ($user->role === 'admin') {
            abort_unless($data['company_id'] == $user->company_id, 403);
        }
        try {
            CourierPolicy::create($data);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create policy. ' . $e->getMessage());
        }

        return redirect()->route('policies.index')->with('success', 'Policy created successfully');
    }

    public function show(CourierPolicy $policy)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $policy->company_id) {
            abort(403, 'You can only view policies from your company');
        }
        return view('policies.show', ['policy' => $policy]);
    }

    public function edit(CourierPolicy $policy)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $policy->company_id) {
            abort(403, 'You can only edit policies from your company');
        }
        if ($user->role === 'admin') {
            $companies = \App\Models\Company::where('id', $user->company_id)->get();
        } else {
            $companies = \App\Models\Company::all();
        }
        return view('policies.edit', ['policy' => $policy, 'companies' => $companies]);
    }

    public function update(Request $request, CourierPolicy $policy)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('courier_policies')->ignore($policy->id)->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);
                })
            ],
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ], [
            'name.unique' => 'A policy with this name already exists for the selected company.'
        ]);
        try {
            $policy->update($data);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update policy. ' . $e->getMessage());
        }

        return redirect()->route('policies.show', $policy)->with('success', 'Policy updated successfully');
    }

    public function destroy(CourierPolicy $policy)
    {
        $policy->delete();
        return redirect()->route('policies.index');
    }

    public function deleted()
    {
        $policies = CourierPolicy::onlyTrashed()->paginate(20);
        return view('policies.deleted', compact('policies'));
    }

    public function restore($id)
    {
        $policy = CourierPolicy::onlyTrashed()->findOrFail($id);
        $policy->restore();
        return redirect()->route('policies.index')->with('status', 'Policy restored');
    }
}
