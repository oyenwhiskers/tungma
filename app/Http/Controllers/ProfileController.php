<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'ic_number' => 'nullable|string',
            'position' => 'nullable|string',
            'email' => 'required|email',
        ]);

        $user = Auth::user();
        $user->update($data);
        return back()->with('status', 'Profile updated');
    }
}
