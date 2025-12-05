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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ]);
        // Force admin to create policies only for their company
        if ($user->role === 'admin') {
            abort_unless($data['company_id'] == $user->company_id, 403);
        }
        CourierPolicy::create($data);
        return redirect()->route('policies.index');
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ]);
        $policy->update($data);
        return redirect()->route('policies.show', $policy);
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
