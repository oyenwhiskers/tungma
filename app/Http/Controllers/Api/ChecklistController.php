<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use Carbon\Carbon;

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
     * List Checklists
     *
     * Display a listing of checklists for a given date (defaults to today),
     * grouped by bus departure time.
     * Returns a JSON response containing the list of departure times and their status.
     *
     * @queryParam date string The date to view checklists for (Y-m-d). Defaults to today's date. Example: 2025-12-14
     *
     * @response 200 {
     *    "success": true,
     *    "data": [
     *         {
     *             "bus_departures_id": 1,
     *             "departure_time": "08:30:00",
     *             "date": "2025-12-15",
     *             "status": "pending",
     *             "checked_by": "-"
     *         }
     *     ]
     * }
     */
    public function index(Request $request)
    {
        // Allow viewing today's checklist (default) or any previous date via ?date=Y-m-d
        $date = $request->query('date');
        $targetDate = $date
            ? Carbon::parse($date)->toDateString()
            : now()->toDateString();

        $bills = Bill::whereDate('date', $targetDate)
            ->with(['checker', 'busDeparture'])
            ->get()
            ->groupBy('bus_departures_id');

        $rows = $bills->map(function ($items, $busDepartureId) {

            $total = $items->count();
            $checkedCount = $items->whereNotNull('checked_by')->count();

            if ($total === 0) {
                $status = 'no data';
            } elseif ($checkedCount > 0) {
                $status = 'success';
            } else {
                $status = 'pending';
            }

            $checkedItem = $items->whereNotNull('checked_by')->first();
            $firstItem = $items->first();
            
            // Get departure time from the relationship
            $departureTime = $firstItem && $firstItem->busDeparture 
                ? $firstItem->busDeparture->departure_time 
                : null;
            
            return [
                'bus_departures_id' => $busDepartureId,
                'departure_time' => $departureTime,
                'date' => $firstItem ? $firstItem->date->format('Y-m-d') : null,
                'status' => $status,
                'checked_by' => $checkedItem && $checkedItem->checker 
                    ? $checkedItem->checker->name 
                    : '-',
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
     * Display the specific checklist for a given bus departure.
     * Returns a JSON response with the list of bills/items for that departure.
     *
     * @urlParam bus_departures_id int required The bus departure ID to view. Example: 1
     *
     * @queryParam date string The date to view (Y-m-d). Defaults to today. Example: 2025-12-15
     *
     * @response 200 {
     *    "success": true,
     *    "data": {
     *        "bus_departures_id": 1,
     *        "departure_time": "08:30:00",
     *        "date": "2025-12-15",
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
    public function show($bus_departures_id, Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        
        $bills = Bill::where('bus_departures_id', $bus_departures_id)
            ->whereDate('date', $date)
            ->with('busDeparture')
            ->get();
        
        $busDeparture = $bills->first()?->busDeparture;

        return response()->json([
            'success' => true,
            'data' => [
                'bus_departures_id' => $bus_departures_id,
                'departure_time' => $busDeparture?->departure_time,
                'date' => $date,
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
        $id = auth()->user()->id;

        Bill::whereIn('id', $request->bill_ids)
            ->update([
                'checked_by' => $id,
                'status' => 'Arrived',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Checklist saved'
        ]);
    }

}
