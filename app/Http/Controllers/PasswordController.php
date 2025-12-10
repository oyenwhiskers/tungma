<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordController extends Controller
{
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return back()->with('status', 'Password changed');
    }

    public function resetToDefault(Request $request, User $user)
    {
        // In a real implementation, fetch default password policy; here use a placeholder
        $defaultPassword = 'TungMa@123';
        $user->password = Hash::make($defaultPassword);
        $user->save();

        // Send verification/notification email (placeholder)
        // Mail::to($user->email)->send(new DefaultPasswordResetMail($user));

        return back()->with('status', 'Password reset to default');
    }
}
