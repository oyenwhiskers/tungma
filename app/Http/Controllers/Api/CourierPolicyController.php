<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourierPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Courier Policies
 *
 * API for fetching courier policies.
 *
 * All results are automatically scoped to the authenticated user's company.
 *
 * @authenticated
 * @header Authorization Bearer {token}
 */
class CourierPolicyController extends Controller
{
    /**
     * List Courier Policies
     *
     * Return all courier policies for the authenticated user's company.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Standard Policy",
     *       "description": "Default courier policy"
     *     }
     *   ]
     * }
     * @response 403 {
     *   "message": "User does not have an associated company"
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'message' => 'User does not have an associated company'
            ], 403);
        }

        $policies = CourierPolicy::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'data' => $policies,
        ]);
    }
}


