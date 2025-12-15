<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;

/**
 * @group Checklist
 *
 * API for managing daily bus checklists.
 *
 * This resource allows viewing and updating checklist status for bus departures.
 * Note: These endpoints return JSON responses for use in mobile apps or API clients.
 *
 * @authenticated
 */
class ChecklistController extends Controller
{
    /**
     * List Today's Checklists
     *
     * Display a listing of checklists for the current date, grouped by bus departure time.
     * Returns a JSON response containing the list of departure times and their status.
     *
     * @response 200 {
     *    "success": true,
     *    "data": [
     *         {
     *             "bus_datetime": "2025-12-15 08:30:00",
     *             "status": "pending",
     *             "checked_by": "-"
     *         }
     *     ]
     * }
     */
    public function index()
    {
        $today = now()->toDateString();

        $bills = Bill::whereDate('bus_datetime', $today)
            ->get()
            ->groupBy('bus_datetime');

        $rows = $bills->map(function ($items, $busDatetime) {

            $total = $items->count();
            $checkedCount = $items->whereNotNull('checked_by')->count();

            if ($total === 0) {
                $status = 'no data';
            } elseif ($checkedCount > 0) {
                $status = 'success';
            } else {
                $status = 'pending';
            }

            return [
                'bus_datetime' => $busDatetime,
                'status' => $status,
                'checked_by' => $items->whereNotNull('checked_by')
                    ->pluck('checked_by')
                    ->first() ?? '-',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $rows->values(),
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @ignore
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show Checklist Details
     *
     * Display the specific checklist for a given bus departure datetime.
     * Returns a JSON response with the list of bills/items for that departure.
     *
     * @urlParam bus_datetime string required The departure datetime to view. Example: 2025-12-15 08:30:00
     *
     * @response 200 {
     *    "success": true,
     *    "data": {
     *        "bus_datetime": "2025-12-15 08:30:00",
     *        "bills": [
     *             {
     *                 "id": 1,
     *                 "bill_code": "INV-001",
     *                 ...
     *             }
     *        ]
     *    }
     * }
     */
    public function show($bus_datetime)
    {
        $bills = Bill::where('bus_datetime', $bus_datetime)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'bus_datetime' => $bus_datetime,
                'bills' => $bills
            ]
        ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @ignore
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @ignore
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Save Checklist
     *
     * Mark selected bills as checked/verified by the authenticated user.
     * Updates the `checked_by` field for the provided bill IDs.
     *
     * @bodyParam bill_ids int[] required Array of Bill IDs that have been checked. Example: [1, 2, 3]
     *
     * @response 200 {
     *    "success": true,
     *    "message": "Checklist saved"
     * }
     */
    public function save(Request $request)
    {
        $userName = auth()->user()->name;

        Bill::whereIn('id', $request->bill_ids)
            ->update([
                'checked_by' => $userName
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Checklist saved'
        ]);
    }

}
