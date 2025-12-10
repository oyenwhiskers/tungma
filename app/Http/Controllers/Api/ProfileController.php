<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * @group Profile Management
 *
 * APIs for managing user profile
 */
class ProfileController extends Controller
{
    /**
     * Get Profile
     * 
     * Retrieve the authenticated user's profile information including personal details,
     * contact information, and role.
     *
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Profile retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "username": "johndoe",
     *     "email": "john@example.com",
     *     "contact_number": "+60123456789",
     *     "date_of_birth": "1990-01-15",
     *     "gender": "male",
     *     "ic_number": "900115-01-1234",
     *     "position": "Software Engineer",
     *     "image": "/storage/users/example.jpg",
     *     "company_id": 1,
     *     "role": "staff",
     *     "created_at": "2025-01-01T00:00:00.000000Z",
     *     "updated_at": "2025-01-10T12:00:00.000000Z"
     *   }
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'contact_number' => $user->contact_number,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'ic_number' => $user->ic_number,
                'position' => $user->position,
                'image' => $user->image ? Storage::url($user->image) : null,
                'company_id' => $user->company->name,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Update Profile
     * 
     * Update the authenticated user's profile information. All fields are optional.
     * Only provided fields will be updated. Role and company_id cannot be changed through this endpoint.
     *
     * @authenticated
     * 
    
     * @bodyParam username string optional The user's username (must be unique). Example: johndoe
     * @bodyParam contact_number string optional The user's contact number (must be unique). Example: +60123456789
     * @bodyParam image file optional The user's profile image (max 5MB, allowed: jpeg, png, jpg, gif)
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Profile updated successfully",
     *   "data": {
     *     "id": 1,
     *     "username": "johndoe",
     *     "contact_number": "+60123456789",
     *     "image": "/storage/users/example.jpg",
     *     "created_at": "2025-01-01T00:00:00.000000Z",
     *     "updated_at": "2025-01-10T12:30:00.000000Z"
     *   }
     * }
     * 
     * @response 422 {
     *   "message": "The contact number has already been taken.",
     *   "errors": {
     *     "contact_number": [
     *       "The contact number has already been taken."
     *     ]
     *   }
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            // 'name' => 'sometimes|required|string|max:255',
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            // 'email' => [
            //     'sometimes',
            //     'required',
            //     'string',
            //     'email',
            //     'max:255',
            //     Rule::unique('users')->ignore($user->id),
            // ],
            'contact_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            // 'date_of_birth' => 'nullable|date|before:today',
            // 'gender' => 'nullable|in:male,female,other',
            // 'ic_number' => 'nullable|string|max:50',
            // 'position' => 'nullable|string|max:255',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }
            
            // Store new image
            $imagePath = $request->file('image')->store('users', 'public');
            $validated['image'] = $imagePath;
        }

        // Update user fields
        foreach ($validated as $key => $value) {
            if ($key !== 'image' || $request->hasFile('image')) {
                $user->{$key} = $value;
            }
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                // 'name' => $user->name,
                'username' => $user->username,
                // 'email' => $user->email,
                'contact_number' => $user->contact_number,
                'image' => $user->image ? Storage::url($user->image) : null,
                // 'date_of_birth' => $user->date_of_birth,
                // 'gender' => $user->gender,
                // 'ic_number' => $user->ic_number,
                // 'position' => $user->position,
                // 'company_id' => $user->company_id,
                // 'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Update Password
     * 
     * Change the authenticated user's password. Requires current password verification.
     * The new password must be at least 8 characters and must be confirmed.
     *
     * @authenticated
     * 
     * @bodyParam current_password string required The user's current password. Example: oldpassword123
     * @bodyParam new_password string required The new password (minimum 8 characters). Example: newpassword123
     * @bodyParam new_password_confirmation string required Confirmation of the new password (must match new_password). Example: newpassword123
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Password updated successfully"
     * }
     * 
     * @response 422 scenario="Current password incorrect" {
     *   "success": false,
     *   "message": "Current password is incorrect",
     *   "errors": {
     *     "current_password": [
     *       "The current password is incorrect."
     *     ]
     *   }
     * }
     * 
     * @response 422 scenario="Validation error" {
     *   "message": "The new password field confirmation does not match.",
     *   "errors": {
     *     "new_password": [
     *       "The new password field confirmation does not match."
     *     ]
     *   }
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Check if current password is correct
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
                'errors' => [
                    'current_password' => ['The current password is incorrect.']
                ]
            ], 422);
        }

        // Update password
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }
}
