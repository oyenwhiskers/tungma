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
     * @group Checklist
     * @authenticated
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

        $user = $request->user();

        $query = Bill::whereDate('date', $targetDate)
            ->with(['checker', 'busDeparture']);

        // Filter by company visibility (sender OR receiver)
        if ($user->role !== 'super_admin') {
            $query->where(function ($q) use ($user) {
                $q->where('from_company_id', $user->company_id)
                    ->orWhere('to_company_id', $user->company_id);
            });
        }

        $bills = $query->get()->groupBy('bus_departures_id');

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
                'date' => $firstItem ? $firstItem->date : null,
                'status' => $status,
                'checked_by' => $checkedItem && $checkedItem->checker
                    ? $checkedItem->checker->name
                    : '-',
            ];
        });

        // Ensure date formatting consistency
        $formattedRows = $rows->values()->map(function ($row) {
            if ($row['date'] instanceof Carbon) {
                $row['date'] = $row['date']->format('Y-m-d');
            }
            return $row;
        });

        return response()->json([
            'success' => true,
            'data' => $formattedRows,
        ]);
    }

    /**
     * Show Checklist Details
     *
     * Display the specific checklist for a given bus departure.
     * Returns a JSON response with the list of bills/items for that departure.
     *
     * @group Checklist
     * @authenticated
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
     *                 "amount": 100.00
     *             }
     *        ]
     *    }
     * }
     */
    public function show($bus_departures_id, Request $request)
    {
        $user = $request->user();
        $date = $request->query('date', now()->toDateString());

        $query = Bill::where('bus_departures_id', $bus_departures_id)
            ->whereDate('date', $date)
            ->with(['busDeparture', 'fromCompany', 'toCompany']);

        // Filter by company visibility:
        // Users can see a bill if they belong to 'from_company' OR 'to_company'
        if ($user->role !== 'super_admin') {
            $query->where(function ($q) use ($user) {
                $q->where('from_company_id', $user->company_id)
                    ->orWhere('to_company_id', $user->company_id);
            });
        }

        $bills = $query->get();
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
     * Save Checklist
     *
     * Mark selected bills as checked/verified by the authenticated user.
     * Updates the `checked_by` field for the provided bill IDs.
     *
     * @group Checklist
     * @authenticated
     * @bodyParam bill_ids int[] required Array of Bill IDs that have been checked. Example: [1, 2, 3]
     *
     * @response 200 {
     *    "success": true,
     *    "message": "Checklist saved successfully!"
     * }
     * @response 403 {
     *    "message": "You are not authorized to update the checklist."
     * }
     */
    public function save(Request $request)
    {
        $request->validate([
            'bill_ids' => 'nullable|array',
            'bill_ids.*' => 'exists:bills,id'
        ]);

        $user = $request->user();
        $userId = $user->id;
        $billIds = $request->input('bill_ids', []);

        // Admins and Super Admins are not allowed to tick/save the checklist
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'You are not authorized to update the checklist.'], 403);
        }

        if (!empty($billIds)) {
            $query = Bill::whereIn('id', $billIds);

            // Filter by company visibility (sender OR receiver)
            if ($user->role !== 'super_admin') {
                $query->where(function ($q) use ($user) {
                    $q->where('from_company_id', $user->company_id)
                        ->orWhere('to_company_id', $user->company_id);
                });
            }

            $query->update([
                'checked_by' => $userId
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Checklist saved successfully!'
        ]);
    }
}
