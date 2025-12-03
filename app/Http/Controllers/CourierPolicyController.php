<?php

namespace App\Http\Controllers;

use App\Models\CourierPolicy;
use Illuminate\Http\Request;

class CourierPolicyController extends Controller
{
    public function index()
    {
        $policies = CourierPolicy::query()->paginate(20);
        return view('policies.index', compact('policies'));
    }

    public function create()
    {
        $companies = \App\Models\Company::all();
        return view('policies.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ]);
        CourierPolicy::create($data);
        return redirect()->route('policies.index');
    }

    public function show(CourierPolicy $policy)
    {
        return view('policies.show', ['policy' => $policy]);
    }

    public function edit(CourierPolicy $policy)
    {
        $companies = \App\Models\Company::all();
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
