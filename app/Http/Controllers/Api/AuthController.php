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
     * Authenticate a user using contact number and password. Returns a Sanctum bearer token
     * that should be included in subsequent API requests in the Authorization header as:
     * `Authorization: Bearer {token}`
     *
     * @group Authentication
     *
     * @bodyParam contact_number string required The user's contact number. Example: +60123456789
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response 200 {
     *   "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
     *   "token_type": "Bearer",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "username": "johndoe",
     *     "email": "john@example.com",
     *     "contact_number": "+60123456789",
     *     "date_of_birth": "1990-01-15",
     *     "gender": "male",
     *     "ic_number": "900115-01-1234",
     *     "position": "Software Engineer",
     *     "company_id": 1,
     *     "role": "staff",
     *     "created_at": "2025-01-01T00:00:00.000000Z",
     *     "updated_at": "2025-01-10T12:00:00.000000Z"
     *   }
     * }
     * 
     * @response 401 {
     *   "message": "Invalid credentials"
     * }
     * 
     * @response 422 {
     *   "message": "The contact number field is required.",
     *   "errors": {
     *     "contact_number": [
     *       "The contact number field is required."
     *     ]
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

    /**
     * Logout (Revoke Access Token)
     * 
     * Revoke the current user's access token and logout the authenticated user.
     * 
     * @group Authentication
     * @authenticated
     * 
     * @header Authorization Bearer {token}
     * 
     * @response 200 scenario="Successful Logout" {
     *   "message": "Logged out successfully"
     * }
     * 
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     * 
     * Log out the currently authenticated user by revoking the current access token.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @scribe
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}

