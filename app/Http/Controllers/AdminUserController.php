<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contact_number' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'ic_number' => 'nullable|string',
            'position' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
            'password' => 'required|string|min:8',
        ]);
        $data['role'] = 'admin';
        $data['password'] = bcrypt($data['password']);
        User::create($data);
        return redirect()->route('admins.index');
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
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id,
            'contact_number' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'ic_number' => 'nullable|string',
            'position' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
        ]);
        $admin->update($data);
        return redirect()->route('admins.show', $admin);
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
