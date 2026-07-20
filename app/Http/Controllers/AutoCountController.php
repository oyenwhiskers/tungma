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
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'company_id' => 'nullable'
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $companyId = $request->company_id;

        // If user is not super_admin, force company_id to their own company
        if (auth()->user()->role !== 'super_admin') {
            $companyId = auth()->user()->company_id;
        }

        $formattedStart = Carbon::parse($startDate)->format('Y-m-d');
        $formattedEnd = Carbon::parse($endDate)->format('Y-m-d');
        $fileName = "autocount_export_{$formattedStart}_to_{$formattedEnd}.xlsx";

        return Excel::download(new AutoCountExport($startDate, $endDate, $companyId), $fileName);
    }
}
