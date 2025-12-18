<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

/**
 * @group Companies
 *
 * API for fetching companies.
 *
 * Currently returns the full list of companies (not scoped by user/company).
 *
 * @authenticated
 * @header Authorization Bearer {token}
 */
class CompanyController extends Controller
{
    /**
     * List Companies
     *
     * Return all companies.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Company A"
     *     }
     *   ]
     * }
     */
    public function index(): JsonResponse
    {
        $companies = Company::orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $companies,
        ]);
    }
}


