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
            
        $type = $request->query('type', 'all'); // 'all', 'ongoing', 'ingoing'

        $user = auth()->user();

        // Filter by company if user is admin or staff
        $query = Bill::whereDate('date', $targetDate)
            ->with(['checker', 'busDeparture', 'fromCompany', 'toCompany']);

        // Filter by company visibility and type (ongoing = outgoing, ingoing = incoming)
        if ($user->role !== 'super_admin') {
            if ($type === 'ongoing') {
                $query->where('from_company_id', $user->company_id);
            } elseif ($type === 'ingoing') {
                $query->where('to_company_id', $user->company_id);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('from_company_id', $user->company_id)
                        ->orWhere('to_company_id', $user->company_id);
                });
            }
        } else {
            if ($type === 'ongoing') {
                $query->whereColumn('from_company_id', '!=', 'to_company_id');
            }
        }

        $bills = $query->get()->groupBy('bus_departures_id');

        $rows = $bills->map(function ($items, $busDepartureId) use ($targetDate, $user) {
            $total = $items->count();
            $checkedCount = $items->whereNotNull('checked_by')->count();

            if ($total === 0) {
                $status = 'no data';
            } elseif ($checkedCount === $total) {
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

            // Determine if outgoing/ingoing for the current user
            $direction = 'Both';
            if ($user->role !== 'super_admin') {
                $hasOutgoing = $items->contains('from_company_id', $user->company_id);
                $hasIncoming = $items->contains('to_company_id', $user->company_id);
                if ($hasOutgoing && !$hasIncoming) {
                    $direction = 'Ongoing (Outgoing)';
                } elseif (!$hasOutgoing && $hasIncoming) {
                    $direction = 'Ingoing (Incoming)';
                }
            } else {
                $direction = 'All Routing';
            }

            return [
                'bus_departures_id' => $busDepartureId,
                'departure_time' => $departureTime,
                'date' => $targetDate,
                'status' => $status,
                'total' => $total,
                'checked_count' => $checkedCount,
                'direction' => $direction,
                'checked_by' => $checkedItem && $checkedItem->checker
                    ? $checkedItem->checker->name
                    : '-',
            ];
        });

        return view('checklists.index', [
            'rows' => $rows->values(),
            'date' => $targetDate,
            'type' => $type
        ]);
    }

    /**
     * Display the specific checklist for a given bus departure.
     */
    public function show($bus_departures_id, Request $request)
    {
        $user = auth()->user();

        // Get the date from query parameter or default to today
        $dateParam = $request->query('date', now()->toDateString());
        $date = Carbon::parse($dateParam)->toDateString();
        $type = $request->query('type', 'all');

        // Start basic query
        $query = Bill::where('bus_departures_id', $bus_departures_id)
            ->whereDate('date', $date)
            ->with(['busDeparture', 'fromCompany', 'toCompany', 'checker']);

        // Filter by company visibility and type
        if ($user->role !== 'super_admin') {
            if ($type === 'ongoing') {
                $query->where('from_company_id', $user->company_id);
            } elseif ($type === 'ingoing') {
                $query->where('to_company_id', $user->company_id);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('from_company_id', $user->company_id)
                        ->orWhere('to_company_id', $user->company_id);
                });
            }
        }

        $bills = $query->get();
        $busDeparture = $bills->first()?->busDeparture;

        return view('checklists.show', [
            'bus_departures_id' => $bus_departures_id,
            'departure_time' => $busDeparture?->departure_time,
            'date' => $date,
            'bills' => $bills,
            'type' => $type
        ]);
    }

    /**
     * Save the checklist - mark selected bills as checked/verified.
     */
    public function save(Request $request)
    {
        $request->validate([
            'bus_departures_id' => 'required',
            'date' => 'required|date',
            'bill_ids' => 'nullable|array',
            'bill_ids.*' => 'exists:bills,id'
        ]);

        $userId = auth()->user()->id;
        $billIds = $request->input('bill_ids', []);
        $busDepartureId = $request->input('bus_departures_id');
        $date = Carbon::parse($request->input('date'))->toDateString();

        $user = auth()->user();

        // Get all bills in this checklist context
        $allBillsQuery = Bill::where('bus_departures_id', $busDepartureId)
            ->whereDate('date', $date);

        // Filter by company visibility
        if ($user->role !== 'super_admin') {
            $allBillsQuery->where(function($q) use ($user) {
                $q->where('from_company_id', $user->company_id)
                  ->orWhere('to_company_id', $user->company_id);
            });
        }

        $allBills = $allBillsQuery->get();

        foreach ($allBills as $bill) {
            if (in_array($bill->id, $billIds)) {
                $bill->update(['checked_by' => $userId]);
            } else {
                $bill->update(['checked_by' => null]);
            }
        }

        return redirect()
            ->route('checklists.index', [
                'date' => $request->input('date'),
                'type' => $request->input('type', 'all')
            ])
            ->with('status', 'Checklist saved successfully!');
    }

    /**
     * Print Proof of Collection (POC) Checklist
     */
    public function print($bus_departures_id, Request $request)
    {
        $user = auth()->user();
        $date = $request->query('date', now()->toDateString());
        $type = $request->query('type', 'all');

        $query = Bill::where('bus_departures_id', $bus_departures_id)
            ->whereDate('date', $date)
            ->with(['busDeparture', 'fromCompany', 'toCompany', 'checker', 'company']);

        if ($user->role !== 'super_admin') {
            if ($type === 'ongoing') {
                $query->where('from_company_id', $user->company_id);
            } elseif ($type === 'ingoing') {
                $query->where('to_company_id', $user->company_id);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('from_company_id', $user->company_id)
                        ->orWhere('to_company_id', $user->company_id);
                });
            }
        }

        $bills = $query->get();

        if ($bills->isEmpty()) {
            return back()->with('error', 'No bills found for this checklist.');
        }
        // Determine terminal name
        $terminal = 'ALL';
        if ($user->role !== 'super_admin' && $user->company) {
            $terminal = $user->company->based_in;
        } else {
            $firstBill = $bills->first();
            if ($type === 'ongoing') {
                $terminal = $firstBill->fromCompany->based_in ?? 'ALL';
            } else {
                $terminal = $firstBill->toCompany->based_in ?? 'ALL';
            }
        }

        $firstBill = $bills->first();
        $fromTerminal = $firstBill->fromCompany->based_in ?? 'ALL';
        $toTerminal = $firstBill->toCompany->based_in ?? 'ALL';

        $pdf = \PDF::loadView('checklists.print', compact('bills', 'date', 'type', 'terminal', 'fromTerminal', 'toTerminal') + ['isPdf' => true])
            ->setPaper('a4', 'landscape');

        return $pdf->stream('poc-checklist-' . $date . '.pdf');
    }

    /**
     * Display the main proof checklist for a given date.
     */
    public function showByDate(Request $request)
    {
        $user = auth()->user();
        $dateParam = $request->query('date', now()->toDateString());
        $date = Carbon::parse($dateParam)->toDateString();
        $type = $request->query('type', 'all');

        // Start basic query (for all departures on this date)
        $query = Bill::whereDate('date', $date)
            ->with(['busDeparture', 'fromCompany', 'toCompany', 'checker'])
            ->orderBy('bill_code', 'asc'); // Sorted by running number/bill code to keep them continuous!

        // Filter by company visibility and type
        if ($user->role !== 'super_admin') {
            if ($type === 'ongoing') {
                $query->where('from_company_id', $user->company_id);
            } elseif ($type === 'ingoing') {
                $query->where('to_company_id', $user->company_id);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('from_company_id', $user->company_id)
                        ->orWhere('to_company_id', $user->company_id);
                });
            }
        }

        $bills = $query->get();

        return view('checklists.show_by_date', [
            'date' => $date,
            'bills' => $bills,
            'type' => $type
        ]);
    }

    /**
     * Save the main proof checklist - mark selected bills as checked/verified.
     */
    public function saveByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'bill_ids' => 'nullable|array',
            'bill_ids.*' => 'exists:bills,id'
        ]);

        $userId = auth()->user()->id;
        $billIds = $request->input('bill_ids', []);
        $date = Carbon::parse($request->input('date'))->toDateString();

        $user = auth()->user();

        // Get all bills in this checklist context (for the entire date)
        $allBillsQuery = Bill::whereDate('date', $date);

        // Filter by company visibility
        if ($user->role !== 'super_admin') {
            $allBillsQuery->where(function($q) use ($user) {
                $q->where('from_company_id', $user->company_id)
                  ->orWhere('to_company_id', $user->company_id);
            });
        }

        $allBills = $allBillsQuery->get();

        foreach ($allBills as $bill) {
            if (in_array($bill->id, $billIds)) {
                $bill->update(['checked_by' => $userId]);
            } else {
                $bill->update(['checked_by' => null]);
            }
        }

        return redirect()
            ->route('checklists.showByDate', [
                'date' => $request->input('date'),
                'type' => $request->input('type', 'all')
            ])
            ->with('status', 'Main Proof Checklist saved successfully!');
    }

    /**
     * Print Proof of Collection (POC) Checklist for the entire date.
     */
    public function printByDate(Request $request)
    {
        $user = auth()->user();
        $date = $request->query('date', now()->toDateString());
        $type = $request->query('type', 'all');

        $query = Bill::whereDate('date', $date)
            ->with(['busDeparture', 'fromCompany', 'toCompany', 'checker', 'company'])
            ->orderBy('bill_code', 'asc'); // Sorted by running number/bill code to keep them continuous!

        if ($user->role !== 'super_admin') {
            if ($type === 'ongoing') {
                $query->where('from_company_id', $user->company_id);
            } elseif ($type === 'ingoing') {
                $query->where('to_company_id', $user->company_id);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('from_company_id', $user->company_id)
                        ->orWhere('to_company_id', $user->company_id);
                });
            }
        }

        $bills = $query->get();

        if ($bills->isEmpty()) {
            return back()->with('error', 'No bills found for this date.');
        }

        // Determine terminal name
        $terminal = 'ALL';
        if ($user->role !== 'super_admin' && $user->company) {
            $terminal = $user->company->based_in;
        } else {
            $firstBill = $bills->first();
            if ($type === 'ongoing') {
                $terminal = $firstBill->fromCompany->based_in ?? 'ALL';
            } else {
                $terminal = $firstBill->toCompany->based_in ?? 'ALL';
            }
        }

        $firstBill = $bills->first();
        $fromTerminal = $firstBill->fromCompany->based_in ?? 'ALL';
        $toTerminal = $firstBill->toCompany->based_in ?? 'ALL';

        $pdf = \PDF::loadView('checklists.print', compact('bills', 'date', 'type', 'terminal', 'fromTerminal', 'toTerminal') + ['isPdf' => true])
            ->setPaper('a4', 'landscape');

        return $pdf->stream('main-poc-checklist-' . $date . '.pdf');
    }
}

