<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::query()->paginate(20);
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'email' => 'nullable|email',
        ]);
        Company::create($data);
        return redirect()->route('companies.index');
    }

    public function show(Company $company)
    {
        return view('companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'email' => 'nullable|email',
        ]);
        $company->update($data);
        return redirect()->route('companies.show', $company);
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()->route('companies.index');
    }

    public function deleted()
    {
        $companies = Company::onlyTrashed()->paginate(20);
        return view('companies.deleted', compact('companies'));
    }

    public function restore($id)
    {
        $company = Company::onlyTrashed()->findOrFail($id);
        $company->restore();
        return redirect()->route('companies.index')->with('status', 'Company restored');
    }
}
