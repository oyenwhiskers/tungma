<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusDepartures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Bus Departures
 *
 * API for fetching bus departure times.
 *
 * All results are automatically scoped to the authenticated user's company.
 *
 * @authenticated
 * @header Authorization Bearer {token}
 */
class BusDeparturesController extends Controller
{
    /**
     * List Bus Departures
     *
     * Return all bus departure times for the authenticated user's company.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "departure_time": "08:30:00"
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

        $departures = BusDepartures::where('company_id', $user->company_id)
            ->orderBy('departure_time')
            ->get(['id', 'departure_time']);

        return response()->json([
            'data' => $departures,
        ]);
    }
}


