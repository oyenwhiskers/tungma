<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class StaffUserController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->role === 'admin') {
            // Admin only sees staff from their company
            $staff = User::where('role', 'staff')->where('company_id', $user->company_id)->paginate(20);
        } else {
            $staff = User::where('role', 'staff')->paginate(20);
        }
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        $user = auth()->user();
        $company = null;
        if ($user->role === 'admin') {
            $company = $user->company;
        }
        return view('staff.create', compact('company'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
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
            'start_date' => 'nullable|date',
            'password' => 'required|string|min:8',
        ]);
        // Force staff to be assigned to admin's company if admin
        if ($user->role === 'admin') {
            $data['company_id'] = $user->company_id;
        }
        $data['role'] = 'staff';
        $data['password'] = bcrypt($data['password']);
        User::create($data);
        return redirect()->route('staff.index');
    }

    public function show(User $staff)
    {
        abort_unless($staff->role === 'staff', 404);
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $staff->company_id) {
            abort(403, 'You can only view staff from your company');
        }
        return view('staff.show', ['staff' => $staff]);
    }

    public function edit(User $staff)
    {
        abort_unless($staff->role === 'staff', 404);
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $staff->company_id) {
            abort(403, 'You can only edit staff from your company');
        }
        $company = null;
        if ($user->role === 'admin') {
            $company = $user->company;
        }
        return view('staff.edit', ['staff' => $staff, 'company' => $company]);
    }

    public function update(Request $request, User $staff)
    {
        abort_unless($staff->role === 'staff', 404);
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $staff->company_id) {
            abort(403, 'You can only edit staff from your company');
        }
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
            'start_date' => 'nullable|date',
        ]);
        // Force company_id to remain unchanged if admin
        if ($user->role === 'admin') {
            $data['company_id'] = $staff->company_id;
        }
        $staff->update($data);
        return redirect()->route('staff.show', $staff);
    }

    public function destroy(User $staff)
    {
        abort_unless($staff->role === 'staff', 404);
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $staff->company_id) {
            abort(403, 'You can only delete staff from your company');
        }
        $staff->delete();
        return redirect()->route('staff.index');
    }

    public function deleted()
    {
        $user = auth()->user();
        if ($user->role === 'admin') {
            $staff = User::onlyTrashed()->where('role', 'staff')->where('company_id', $user->company_id)->paginate(20);
        } else {
            $staff = User::onlyTrashed()->where('role', 'staff')->paginate(20);
        }
        return view('staff.deleted', compact('staff'));
    }

    public function restore($id)
    {
        $staff = User::onlyTrashed()->where('role', 'staff')->findOrFail($id);
        $user = auth()->user();
        if ($user->role === 'admin' && $user->company_id !== $staff->company_id) {
            abort(403, 'You can only restore staff from your company');
        }
        $staff->restore();
        return redirect()->route('staff.index')->with('status', 'Staff restored');
    }
}
