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
            'date' => 'required|date',
            'company_id' => 'nullable'
        ]);

        $date = $request->date;
        $companyId = $request->company_id;

        // If user is not super_admin, force company_id to their own company
        if (auth()->user()->role !== 'super_admin') {
            $companyId = auth()->user()->company_id;
        }

        $formattedDate = Carbon::parse($date)->format('Y-m-d');
        $fileName = "autocount_export_{$formattedDate}.xlsx";

        return Excel::download(new AutoCountExport($date, $companyId), $fileName);
    }
}
