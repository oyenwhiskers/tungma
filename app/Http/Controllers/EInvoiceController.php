<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\ECustomer;
use App\Exports\TaxEntityExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EInvoiceController extends Controller
{
    /**
     * Display a list of all bills that have an e-Invoice request.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get bills that have an associated ECustomer record
        $query = Bill::whereHas('eCustomer')
            ->with(['eCustomer', 'company', 'fromCompany', 'toCompany']);

        // Apply company filter for admin
        if ($user->role === 'admin') {
            $query->where('company_id', $user->company_id);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_code', 'like', "%{$search}%")
                  ->orWhereHas('eCustomer', function ($subQ) use ($search) {
                      $subQ->where('tin_number', 'like', "%{$search}%")
                           ->orWhere('customer_name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting functionality
        $sort = $request->get('sort', 'latest');
        if ($sort === 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $bills = $query->paginate(20)->withQueryString();

        return view('e_invoice.index', compact('bills'));
    }

    /**
     * Show preview screen of unique Debtors pending export.
     */
    public function exportPreview(Request $request)
    {
        $user = auth()->user();
        
        $query = ECustomer::with('bill.company');

        // Apply company filter
        if ($user->role === 'admin') {
            $query->whereHas('bill', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        } elseif ($request->filled('company_id') && $request->company_id !== 'all') {
            $query->whereHas('bill', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        // Apply date filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Apply status filter
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('is_exported', false);
            } elseif ($request->status === 'downloaded') {
                $query->where('is_exported', true);
            }
        } else {
            // default to pending if not specified
            $query->where('is_exported', false);
        }

        // Get requests matching filters
        $pending = $query->latest()->get();
            
        // Use unique to strip out duplicate TINs
        $uniqueDebtors = $pending->unique('tin_number');

        return view('e_invoice.export_preview', compact('uniqueDebtors'));
    }

    /**
     * Export the Tax Entity template format for AutoCount.
     */
    public function exportTaxEntity(Request $request)
    {
        $tins = $request->input('tins', []);
        
        if (empty($tins)) {
             return redirect()->back()->with('error', 'No TIN numbers selected for export.');
        }

        // Get the latest distinct records for each selected TINs securely to export
        $customersToExport = collect();
        foreach($tins as $tin) {
            $latestForTin = ECustomer::with('bill')
                                ->where('tin_number', $tin)
                                ->latest()
                                ->first();
            if($latestForTin) {
                $customersToExport->push($latestForTin);
            }
        }

        // Mark ALL bills that share this TIN as exported!
        ECustomer::whereIn('tin_number', $tins)->update(['is_exported' => true]);

        // Generate export filename with today's date
        $filename = 'TaxEntity_Import_' . now()->format('Ymd') . '.xlsx';
        
        return Excel::download(new TaxEntityExport($customersToExport), $filename);
    }

    /**
     * Toggle the exported status of a specific e-invoice request.
     */
    public function toggleStatus($id)
    {
        $eCustomer = ECustomer::findOrFail($id);
        $eCustomer->update(['is_exported' => !$eCustomer->is_exported]);
        
        return redirect()->back()->with('success', 'Status updated successfully.');
    }
}
