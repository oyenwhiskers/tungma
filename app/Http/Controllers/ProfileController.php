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
        $user = Auth::user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['nullable', 'string', 'max:255', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)->withoutTrashed()],
            'contact_number' => ['nullable', 'string', 'regex:/^\+60 ?1\d ?\d{3,4} ?\d{4}$/', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)->withoutTrashed()],
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'ic_number' => ['nullable', 'string', 'regex:/^[0-9]+$/', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)->withoutTrashed()],
            'position' => 'nullable|string',
            'start_date' => 'nullable|date',
            'email' => ['required', 'email', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)->withoutTrashed()],
        ], [
            'contact_number.regex' => 'Contact number must be in the format +60 1x xxxx xxxx',
            'ic_number.regex' => 'IC number must contain only numbers without dashes',
        ]);

        $user = Auth::user();
        $user->update($data);
        return back()->with('status', 'Profile updated');
    }
}
