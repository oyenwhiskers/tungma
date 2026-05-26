<?php

namespace App\Http\Controllers;

use App\Models\Receiver;
use App\Models\Company;
use Illuminate\Http\Request;

class ReceiverController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Receiver::query();

        if ($user->role === 'admin') {
            $query->where('company_id', $user->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $receivers = $query->with('company')->latest()->paginate(15)->withQueryString();

        return view('receivers.index', compact('receivers'));
    }

    public function create()
    {
        $user = auth()->user();
        $companies = $user->role === 'super_admin' ? Company::all() : Company::where('id', $user->company_id)->get();
        return view('receivers.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->role === 'admin' ? $user->company_id : $request->company_id;
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('receivers')->where(function ($query) use ($companyId, $request) {
                    return $query->where('company_id', $companyId)
                                 ->where('contact_number', $request->contact_number);
                })
            ],
            'contact_number' => 'nullable|string|max:255',
            'company_id' => $user->role === 'super_admin' ? 'required|exists:companies,id' : 'nullable',
        ], [
            'name.unique' => 'A receiver with this name and contact number already exists in this company.'
        ]);

        if ($user->role === 'admin') {
            $validated['company_id'] = $user->company_id;
        }

        Receiver::create($validated);

        return redirect()->route('receivers.index')->with('success', 'Receiver created successfully.');
    }

    public function edit(Receiver $receiver)
    {
        $user = auth()->user();
        // Check access
        if ($user->role === 'admin' && $receiver->company_id !== $user->company_id) {
            abort(403);
        }

        $companies = $user->role === 'super_admin' ? Company::all() : Company::where('id', $user->company_id)->get();
        return view('receivers.edit', compact('receiver', 'companies'));
    }

    public function update(Request $request, Receiver $receiver)
    {
        $user = auth()->user();
        // Check access
        if ($user->role === 'admin' && $receiver->company_id !== $user->company_id) {
            abort(403);
        }

        $companyId = $user->role === 'admin' ? $user->company_id : $request->company_id;
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('receivers')->ignore($receiver->id)->where(function ($query) use ($companyId, $request) {
                    return $query->where('company_id', $companyId)
                                 ->where('contact_number', $request->contact_number);
                })
            ],
            'contact_number' => 'nullable|string|max:255',
            'company_id' => $user->role === 'super_admin' ? 'required|exists:companies,id' : 'nullable',
        ], [
            'name.unique' => 'A receiver with this name and contact number already exists in this company.'
        ]);

        if ($user->role === 'admin') {
            $validated['company_id'] = $user->company_id;
        }

        $receiver->update($validated);

        return redirect()->route('receivers.index')->with('success', 'Receiver updated successfully.');
    }

    public function destroy(Receiver $receiver)
    {
        $user = auth()->user();
        if ($user->role === 'admin' && $receiver->company_id !== $user->company_id) {
            abort(403);
        }

        $receiver->delete();
        return redirect()->route('receivers.index')->with('success', 'Receiver deleted successfully.');
    }

    public function deleted(Request $request)
    {
        $user = auth()->user();
        $query = Receiver::onlyTrashed();

        if ($user->role === 'admin') {
            $query->where('company_id', $user->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $receivers = $query->latest('deleted_at')->paginate(15)->withQueryString();

        return view('receivers.deleted', compact('receivers'));
    }

    public function restore($id)
    {
        $receiver = Receiver::onlyTrashed()->findOrFail($id);
        $user = auth()->user();

        if ($user->role === 'admin' && $receiver->company_id !== $user->company_id) {
            abort(403);
        }

        $receiver->restore();
        return redirect()->route('receivers.deleted')->with('success', 'Receiver restored successfully.');
    }
}
