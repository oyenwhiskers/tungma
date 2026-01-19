<?php

namespace App\Http\Controllers;

use App\Models\MsicCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MsicController extends Controller
{
    /**
     * Search for MSIC codes by digit or description.
     * Use this for frontend autocomplete/dropdowns.
     */
    public function search(Request $request): JsonResponse
    {
        // 1. Get and sanitize the input
        $query = trim($request->get('q'));

        // 2. Return empty if the query is too short (saves server resources)
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // 3. Execute search
        // We search for codes starting with the number OR descriptions containing the text
        $results = MsicCode::where('code', 'LIKE', $query . '%')
            ->orWhere('description', 'LIKE', '%' . $query . '%')
            ->orderBy('code', 'asc')
            ->limit(15) // Maintain high performance by limiting results
            ->get(['code as id', 'description as text']); 

        // 4. Return as standard JSON
        return response()->json($results);
    }
}
