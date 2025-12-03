<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class StaffUserController extends Controller
{
    public function index()
    {
        $staff = User::where('role', 'staff')->paginate(20);
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        return view('staff.create');
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
        $data['role'] = 'staff';
        $data['password'] = bcrypt($data['password']);
        User::create($data);
        return redirect()->route('staff.index');
    }

    public function show(User $staff)
    {
        abort_unless($staff->role === 'staff', 404);
        return view('staff.show', ['staff' => $staff]);
    }

    public function edit(User $staff)
    {
        abort_unless($staff->role === 'staff', 404);
        return view('staff.edit', ['staff' => $staff]);
    }

    public function update(Request $request, User $staff)
    {
        abort_unless($staff->role === 'staff', 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $staff->id,
            'contact_number' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'ic_number' => 'nullable|string',
            'position' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
        ]);
        $staff->update($data);
        return redirect()->route('staff.show', $staff);
    }

    public function destroy(User $staff)
    {
        abort_unless($staff->role === 'staff', 404);
        $staff->delete();
        return redirect()->route('staff.index');
    }

    public function deleted()
    {
        $staff = User::onlyTrashed()->where('role', 'staff')->paginate(20);
        return view('staff.deleted', compact('staff'));
    }

    public function restore($id)
    {
        $staff = User::onlyTrashed()->where('role', 'staff')->findOrFail($id);
        $staff->restore();
        return redirect()->route('staff.index')->with('status', 'Staff restored');
    }
}
