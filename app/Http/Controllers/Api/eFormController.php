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
     * @bodyParam msic_code string[] The MSIC codes. Example: ["01111", "62010"]
     * @bodyParam address string required The address. Example: 123, Jalan Sultan
     * @bodyParam postcode string required The postcode. Example: 50000
     * @bodyParam city string required The city. Example: Kuala Lumpur
     * @bodyParam state string required The state. Example: Wilayah Persekutuan
     * @bodyParam country string required The country. Example: Malaysia
     *
     * @response 200 {
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
            'old_business_reg_number' => 'nullable|string',
            'sst_reg_number' => 'nullable|string',
            'msic_code' => 'nullable|array', // Accept array of strings
            'msic_code.*' => 'string|exists:msic_codes,code', // Verify each code exists
            'address' => 'required|string',
            'postcode' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'state_code' => 'nullable|string|size:2',
            'country' => 'required|string',
            'country_code' => 'nullable|string|size:3',
        ]);

        $data = collect($validated)->except('msic_code')->toArray();

        // Handle State Code mapping
        // If 'state' is a 2-digit code, we map it to the name and store the code
        $stateValue = $validated['state'] ?? null;
        if ($stateValue && isset(\App\Models\ECustomer::$stateCodes[$stateValue])) {
            $data['state_code'] = $stateValue;
            $data['state'] = \App\Models\ECustomer::$stateCodes[$stateValue];
        } else {
            // Fallback or if already a name (legacy)
            $data['state_code'] = array_search($stateValue, \App\Models\ECustomer::$stateCodes) ?: null;
        }

        // Handle Country Code
        $data['country_code'] = $validated['country_code'] ?? 'MYS';
        if ($data['country_code'] === 'MYS') {
            $data['country'] = 'Malaysia';
        }

        try {
            $eCustomer = ECustomer::updateOrCreate(
                ['bill_id' => $validated['bill_id']],
                $data
            );

            if (!empty($validated['msic_code'])) {
                // Get IDs for the codes
                $ids = \App\Models\MsicCode::whereIn('code', $validated['msic_code'])->pluck('id');
                $eCustomer->msicCodes()->sync($ids);
            } else {
                // If empty, detach all
                $eCustomer->msicCodes()->detach();
            }

            // --- SCENARIO 2: Manual Update of CashSale ---
            $bill = \App\Models\Bill::with('cashSale')->find($validated['bill_id']);
            
            if ($bill && $bill->cashSale) {
                $bill->cashSale->update([
                    'debtor_name' => $eCustomer->customer_name,
                    // Append TIN to description if beneficial, or keep original
                    // 'description' => $bill->cashSale->description . ' (TIN: ' . $eCustomer->tin_number . ')',
                ]);

                // Regenerate XML with new details
                $xml = $bill->cashSale->generateXml();
                $bill->cashSale->update(['generated_xml' => $xml]);
                
                // Optional: Trigger immediate send to AutoCount if desired
                // AutoCountService::send($xml);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save eForm: ' . $e->getMessage()
            ], 500);
        }
        // ---------------------------------------------

        return response()->json([
            'success' => true,
            'message' => 'Saved successfully'
        ], 200);
    }
}

