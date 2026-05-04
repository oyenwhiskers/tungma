<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        $admins = User::where('role', 'admin')->paginate(20);
        return view('admins.index', compact('admins'));
    }

    public function create()
    {
        return view('admins.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->withoutTrashed()],
            'email' => ['required', 'email', Rule::unique('users')->withoutTrashed()],
            'contact_number' => ['nullable', 'string', Rule::unique('users')->withoutTrashed()],
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'ic_number' => 'nullable|string',
            'position' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
            'start_date' => 'nullable|date',
            'password' => 'required|string|min:8',
        ]);
        $data['role'] = 'admin';
        // Password is automatically hashed by the User model's 'password' => 'hashed' cast

        try {
            User::create($data);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create admin. ' . $e->getMessage());
        }

        return redirect()->route('admins.index')->with('success', 'Admin created successfully');
    }

    public function show(User $admin)
    {
        abort_unless($admin->role === 'admin', 404);
        return view('admins.show', ['admin' => $admin]);
    }

    public function edit(User $admin)
    {
        abort_unless($admin->role === 'admin', 404);
        return view('admins.edit', ['admin' => $admin]);
    }

    public function update(Request $request, User $admin)
    {
        abort_unless($admin->role === 'admin', 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($admin->id)->withoutTrashed()],
            'email' => ['required', 'email', Rule::unique('users')->ignore($admin->id)->withoutTrashed()],
            'contact_number' => ['nullable', 'string', Rule::unique('users')->ignore($admin->id)->withoutTrashed()],
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'ic_number' => 'nullable|string',
            'position' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
            'start_date' => 'nullable|date',
        ]);
        try {
            $admin->update($data);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update admin. ' . $e->getMessage());
        }

        return redirect()->route('admins.show', $admin)->with('success', 'Admin updated successfully');
    }

    public function destroy(User $admin)
    {
        abort_unless($admin->role === 'admin', 404);
        $admin->delete();
        return redirect()->route('admins.index');
    }

    public function deleted()
    {
        $admins = User::onlyTrashed()->where('role', 'admin')->paginate(20);
        return view('admins.deleted', compact('admins'));
    }

    public function restore($id)
    {
        $admin = User::onlyTrashed()->where('role', 'admin')->findOrFail($id);
        $admin->restore();
        return redirect()->route('admins.index')->with('status', 'Admin restored');
    }
}
