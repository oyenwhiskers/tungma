<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * User login with contact number
     *
     * Authenticate a user using `contact_number` and password and return a Sanctum bearer token.
     *
     * @group Auth
     *
     * @bodyParam contact_number string required The user's contact number.
     * @bodyParam password string required The user's password.
     *
     * @response status=200 {
     *   "token": "1|abc...",
     *   "token_type": "Bearer",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "contact_number": "0123456789",
     *     "...": "..."
     *   }
     * }
     * @response status=401 {"message": "Invalid credentials"}
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'contact_number' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('contact_number', $credentials['contact_number'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }
}

