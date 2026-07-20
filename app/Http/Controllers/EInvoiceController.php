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

        // Monthly filter
        if ($request->filled('month')) {
            $monthParts = explode('-', $request->month);
            if (count($monthParts) === 2) {
                $query->whereYear('date', $monthParts[0])
                      ->whereMonth('date', $monthParts[1]);
            }
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
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

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->whereHas('eCustomer', function ($q) {
                    $q->where('is_exported', false);
                });
            } elseif ($request->status === 'done') {
                $query->whereHas('eCustomer', function ($q) {
                    $q->where('is_exported', true);
                });
            }
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

        // Apply month filter
        if ($request->filled('month')) {
            $monthParts = explode('-', $request->month);
            if (count($monthParts) === 2) {
                $query->whereHas('bill', function ($q) use ($monthParts) {
                    $q->whereYear('date', $monthParts[0])
                      ->whereMonth('date', $monthParts[1]);
                });
            }
        }

        // Apply date range filter
        if ($request->filled('start_date')) {
            $query->whereHas('bill', function ($q) use ($request) {
                $q->where('date', '>=', $request->start_date);
            });
        }
        if ($request->filled('end_date')) {
            $query->whereHas('bill', function ($q) use ($request) {
                $q->where('date', '<=', $request->end_date);
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

    /**
     * Export the requests list as a CSV.
     */
    public function exportCsv(Request $request)
    {
        $user = auth()->user();

        $query = Bill::whereHas('eCustomer')
            ->with(['eCustomer', 'company']);

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

        // Monthly filter
        if ($request->filled('month')) {
            $monthParts = explode('-', $request->month);
            if (count($monthParts) === 2) {
                $query->whereYear('date', $monthParts[0])
                      ->whereMonth('date', $monthParts[1]);
            }
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $bills = $query->latest('date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="E-Invoice_Requests_' . now()->format('Ymd') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function () use ($bills) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Bill Code', 'Date', 'Customer Name', 'TIN Number', 'Identity Type', 'Identity No', 'Status', 'Amount (RM)']);

            foreach ($bills as $bill) {
                fputcsv($file, [
                    $bill->bill_code,
                    $bill->date->format('Y-m-d'),
                    $bill->eCustomer->customer_name ?? 'N/A',
                    $bill->eCustomer->tin_number ?? 'N/A',
                    $bill->eCustomer->identity_type ?? 'MyKAD',
                    '="' . ($bill->eCustomer->customer_ic ?: $bill->eCustomer->business_reg_number) . '"',
                    $bill->eCustomer->is_exported ? 'Done' : 'Pending',
                    number_format($bill->amount, 2, '.', '')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Perform bulk action (mark as Done or Pending) on selected or all filtered requests.
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $scope = $request->input('scope');
        
        if (!in_array($action, ['mark_done', 'mark_pending'])) {
            return redirect()->back()->with('error', 'Invalid action.');
        }

        $isExportedVal = ($action === 'mark_done');

        if ($scope === 'selected') {
            $ids = $request->input('ids', []);
            if (!empty($ids)) {
                ECustomer::whereIn('id', $ids)->update(['is_exported' => $isExportedVal]);
            }
        } elseif ($scope === 'all') {
            $user = auth()->user();
            $query = ECustomer::query();

            // Apply company filter
            if ($user->role === 'admin') {
                $query->whereHas('bill', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('bill', function ($q) use ($search) {
                    $q->where('bill_code', 'like', "%{$search}%");
                })->orWhere('tin_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            }

            // Apply monthly filter
            if ($request->filled('month')) {
                $monthParts = explode('-', $request->month);
                if (count($monthParts) === 2) {
                    $query->whereHas('bill', function ($q) use ($monthParts) {
                        $q->whereYear('date', $monthParts[0])
                          ->whereMonth('date', $monthParts[1]);
                    });
                }
            }

            // Apply date range filter
            if ($request->filled('start_date')) {
                $query->whereHas('bill', function ($q) use ($request) {
                    $q->where('date', '>=', $request->start_date);
                });
            }
            if ($request->filled('end_date')) {
                $query->whereHas('bill', function ($q) use ($request) {
                    $q->where('date', '<=', $request->end_date);
                });
            }

            $query->update(['is_exported' => $isExportedVal]);
        }

        return redirect()->back()->with('success', 'Bulk action completed successfully.');
    }
}
