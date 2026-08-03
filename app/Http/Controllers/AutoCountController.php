<?php

namespace App\Http\Controllers;

use App\Exports\AutoCountExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class AutoCountController extends Controller
{
    /**
     * Export bills to AutoCount Excel format for a specific date.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $billIds = session('export_bill_ids');

        if ($billIds && is_array($billIds) && count($billIds) > 0) {
            $fileName = "autocount_export_selected_" . count($billIds) . "_bills.xlsx";
            session()->forget('export_bill_ids');
            return Excel::download(new AutoCountExport(null, null, null, $billIds), $fileName);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'company_id' => 'nullable'
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $companyId = $request->company_id;

        if (auth()->user()->role !== 'super_admin') {
            $companyId = auth()->user()->company_id;
        }

        $formattedStart = Carbon::parse($startDate)->format('Y-m-d');
        $formattedEnd = Carbon::parse($endDate)->format('Y-m-d');
        $fileName = "autocount_export_{$formattedStart}_to_{$formattedEnd}.xlsx";

        return Excel::download(new AutoCountExport($startDate, $endDate, $companyId), $fileName);
    }
}
