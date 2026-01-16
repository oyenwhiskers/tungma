<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\ECustomer;
use App\Http\Controllers\Controller;

/**
 * @group eForm
 *
 * APIs for handling electronic forms
 */
class eFormController extends Controller
{
    /**
     * Submit eForm
     *
     * Store a new e-customer record.
     *
     * @unauthenticated
     *
     * @bodyParam date_time date required The date and time of the submission. Example: 2023-10-27 10:00:00
     * @bodyParam bill_id integer required The ID of the related bill. Example: 1
     * @bodyParam amount numeric required The amount involved. Example: 150.50
     * @bodyParam tin_number string required The Tax Identification Number. Example: C234567890
     * @bodyParam customer_name string The name of the customer. Example: John Doe
     * @bodyParam customer_type string required The type of customer. Example: Individual
     * @bodyParam contact_number string The contact number. Example: +60123456789
     * @bodyParam email_address string The email address. Example: john@example.com
     * @bodyParam identity_type string The type of identity document. Example: NRIC
     * @bodyParam customer_ic string The customer's IC number. Example: 900101-14-1234
     * @bodyParam business_reg_number string The business registration number. Example: 202301001234
     * @bodyParam sst_reg_number string The SST registration number. Example: W10-1808-32000000
     * @bodyParam address string required The address. Example: 123, Jalan Sultan
     * @bodyParam postcode string required The postcode. Example: 50000
     * @bodyParam city string required The city. Example: Kuala Lumpur
     * @bodyParam state string required The state. Example: Wilayah Persekutuan
     * @bodyParam country string required The country. Example: Malaysia
     *
     * @response 201 {
     *  "success": true,
     *  "message": "Saved successfully"
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_time' => 'required|date',
            'bill_id' => 'required|exists:bills,id',
            'amount' => 'required|numeric',
            'tin_number' => 'required|string',
            'customer_name' => 'nullable|string',
            'customer_type' => 'required|string',
            'contact_number' => 'nullable|string',
            'email_address' => 'nullable|email',
            'identity_type' => 'nullable|string',
            'customer_ic' => 'nullable|string',
            'business_reg_number' => 'nullable|string',
            'sst_reg_number' => 'nullable|string',
            'address' => 'required|string',
            'postcode' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
        ]);

        ECustomer::updateOrCreate(
            ['bill_id' => $validated['bill_id']],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Saved successfully'
        ], 200);
    }
}

