<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MsicCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group MSIC Codes
 *
 * APIs for searching and retrieving MSIC (Malaysia Standard Industrial Classification) codes.
 */
class MsicController extends Controller
{
    /**
     * Search MSIC Codes
     *
     * Search for MSIC codes by digit or description.
     * Use this for frontend autocomplete/dropdowns.
     *
     * @queryParam q string The search term. Return all if empty. Example: 620
     * 
     * @response [
     *  {
     *      "id": "62010",
     *      "text": "Computer programming activities"
     *  },
     *  {
     *      "id": "62021",
     *      "text": "Computer consultancy"
     *  }
     * ]
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->get('q'));
        $msicQuery = MsicCode::query();

        if (!empty($query)) {
            $msicQuery->where(function ($q) use ($query) {
                $q->where('code', 'LIKE', $query . '%')
                    ->orWhere('description', 'LIKE', '%' . $query . '%');
            })->limit(15);
        }

        $results = $msicQuery->orderBy('code', 'asc')
            ->toBase() // Return stdClass objects to prevent Eloquent from casting 'id' to integer
            ->get(['code as id', 'description as text']);

        return response()->json($results);
    }
}
