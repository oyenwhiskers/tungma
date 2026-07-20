<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TransitSheetController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::orderBy('name')->get();

        $fromCompanyId = $request->input('from_company_id');
        
        $toCompanyIds = $request->input('to_company_id');
        if (!is_array($toCompanyIds)) {
            $toCompanyIds = $toCompanyIds ? [$toCompanyIds] : [];
        }
        $toCompanyIds = array_filter($toCompanyIds);
        
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Don't fetch bills in index anymore, just pass the parameters for the PDF iframe
        $showPdf = false;
        $pdfUrl = '';
        
        if ($fromCompanyId) {
            $showPdf = true;
            $pdfUrl = route('transit-sheets.pdf', [
                'from_company_id' => $fromCompanyId,
                'to_company_id' => $toCompanyIds,
                'start_date' => $startDate,
                'end_date' => $endDate,
                't' => time()
            ]);
        }

        return view('transit_sheets.index', compact(
            'companies',
            'fromCompanyId',
            'toCompanyIds',
            'startDate',
            'endDate',
            'showPdf',
            'pdfUrl'
        ));
    }

    public function pdf(Request $request)
    {
        // Normalize to_company_id to an array and remove empty values
        $toCompanyIds = $request->input('to_company_id');
        if (!is_array($toCompanyIds)) {
            $toCompanyIds = $toCompanyIds ? [$toCompanyIds] : [];
        }
        $toCompanyIds = array_filter($toCompanyIds);
        $request->merge(['to_company_id' => $toCompanyIds]);

        $request->validate([
            'from_company_id' => 'required|exists:companies,id',
            'to_company_id' => 'nullable|array',
            'to_company_id.*' => 'exists:companies,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $fromCompany = Company::findOrFail($request->from_company_id);
        
        $toCompanies = Company::whereIn('id', $toCompanyIds)->get();
        
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $query = Bill::where('from_company_id', $request->from_company_id)
            ->whereBetween('date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);

        if (!empty($toCompanyIds)) {
            $query->whereIn('to_company_id', $toCompanyIds);
        }

        $bills = $query->orderBy('bill_code')->get();

        // Calculate totals
        $totalCash = 0;
        $totalCod = 0;

        foreach ($bills as $bill) {
            $method = strtolower($bill->payment_details['method'] ?? '');
            if (in_array($method, ['cash', 'qr', 'a/c', 'ewallet', 'e-wallet', 'e_wallet', 'e-wallet/qr', 'ewallet/qr', 'e_wallet_qr'])) {
                $totalCash += $bill->amount;
            } elseif ($method === 'cod') {
                $totalCod += $bill->amount;
            }
        }

        $grandTotal = $totalCash + $totalCod;

        // A4 page splitting logic
        // A4 page splitting logic
        // Set to 100 (50 left, 50 right) to fit more items per page and save paper.
        $perPage = 100;
        $pages = $bills->chunk($perPage)->map(function ($pageBills) {
            $half = ceil($pageBills->count() / 2);
            // Splitting into left side and right side
            return [
                'left' => $pageBills->slice(0, $half),
                'right' => $pageBills->slice($half)
            ];
        });

        $pdf = \PDF::loadView('transit_sheets.pdf', compact(
            'fromCompany',
            'toCompanies',
            'startDate',
            'endDate',
            'pages',
            'totalCash',
            'totalCod',
            'grandTotal'
        ))->setPaper('a4', 'portrait');

        $fileName = 'transit-sheet-' . ($fromCompany->bill_id_prefix ?? 'FROM') . '-' . $startDate . '.pdf';
        
        return $pdf->stream($fileName);
    }
}
