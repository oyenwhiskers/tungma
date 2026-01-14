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
     *       "name": "Company A",
     *       "contact_number": "0123456789",
     *       "address": "123 Jalan Ampang",
     *       "email": "company@example.com",
     *       "based_in": "Kuala Lumpur",
     *       "registration_number": "123456-A",
     *       "sst_number": "W10-1808-32000455",
     *       "bill_id_prefix": "INV",
     *       "created_at": "2023-01-01T00:00:00.000000Z",
     *       "updated_at": "2023-01-01T00:00:00.000000Z",
     *       "deleted_at": null
     *     }
     *   ]
     * }
     */
    public function index(): JsonResponse
    {
        $companies = Company::orderBy('name')
            ->get();

        return response()->json([
            'data' => $companies,
        ]);
    }
}


