<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    /**
     * Display a listing of checklists for a given date (defaults to today).
     */
    public function index(Request $request)
    {
        // Allow viewing today's checklist (default) or any previous date via ?date=Y-m-d
        $date = $request->query('date');
        $targetDate = $date
            ? Carbon::parse($date)->toDateString()
            : now()->toDateString();

        $user = auth()->user();

        // Filter by company if user is admin or staff
        $query = Bill::whereDate('date', $targetDate)
            ->with(['checker', 'busDeparture']);

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
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

        return view('checklists.index', [
            'rows' => $rows->values(),
            'date' => $targetDate
        ]);
    }

    /**
     * Display the specific checklist for a given bus departure.
     */
    public function show($bus_departures_id, Request $request)
    {
        $user = auth()->user();
        
        // Get the date from query parameter or default to today
        $date = $request->query('date', now()->toDateString());

        // Filter by company if user is admin or staff
        $query = Bill::where('bus_departures_id', $bus_departures_id)
            ->whereDate('date', $date)
            ->with('busDeparture');

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
        }

        $bills = $query->get();
        $busDeparture = $bills->first()?->busDeparture;

        return view('checklists.show', [
            'bus_departures_id' => $bus_departures_id,
            'departure_time' => $busDeparture?->departure_time,
            'date' => $date,
            'bills' => $bills
        ]);
    }

    /**
     * Save the checklist - mark selected bills as checked/verified.
     */
    public function save(Request $request)
    {
        $request->validate([
            'bill_ids' => 'nullable|array',
            'bill_ids.*' => 'exists:bills,id'
        ]);

        $userId = auth()->user()->id;
        $billIds = $request->input('bill_ids', []);

        $user = auth()->user();

        // Get all bills for this checklist
        if (!empty($billIds)) {
            $query = Bill::whereIn('id', $billIds);

            // Filter by company if user is not super admin
            if ($user->role !== 'super_admin') {
                $query->where('company_id', $user->company_id);
            }

            $query->update([
                'checked_by' => $userId
            ]);
        }

        return redirect()
            ->route('checklists.index')
            ->with('status', 'Checklist saved successfully!');
    }
}

