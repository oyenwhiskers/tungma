<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * @group Bills API
 *
 * Bills management API for mobile applications.
 *
 * This API provides endpoints for managing bills in a multi-tenant system.
 * All bill operations are automatically scoped to the authenticated user's company.
 *
 * ## Authentication
 * All endpoints require authentication via Laravel Sanctum. Include the bearer token in the Authorization header:
 * ```
 * Authorization: Bearer {your-token}
 * ```
 *
 * ## Company Scoping
 * The `company_id` is automatically set from the authenticated user's company.
 * Users can only access bills belonging to their company.
 *
 * ## Bill Code Generation
 * Bill codes are automatically generated using the company's bill ID prefix followed by a 6-digit running number.
 * Format: `{PREFIX}{NUMBER}` (e.g., `BILL000001`, `INV000001`)
 *
 * ## Voiding Bills
 * Bills cannot be edited once created. To correct a mistake:
 * 1. Void the incorrect bill using the DELETE endpoint
 * 2. Create a new bill with the correct information
 *
 * Voided bills are soft-deleted and will not appear in index or show endpoints.
 *
 * @header Authorization Bearer {token} Example: Bearer 1|abc123def456...
 * @authenticated
 */
class BillController extends Controller
{
    /**
     * Generate the next bill code for a company
     *
     * @param int $companyId
     * @return string
     */
    private function generateNextBillCode($companyId)
    {
        $company = Company::findOrFail($companyId);

        if (empty($company->bill_id_prefix)) {
            throw new \Exception('Company does not have a bill ID prefix set. Please set a prefix in company settings.');
        }

        // Find the latest bill for this company
        $latestBill = Bill::where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;

        if ($latestBill && !empty($latestBill->bill_code)) {
            // Extract the number part from the latest bill code
            $prefix = $company->bill_id_prefix;
            $latestCode = $latestBill->bill_code;

            // Remove the prefix from the beginning of the code
            if (str_starts_with($latestCode, $prefix)) {
                $numberPart = substr($latestCode, strlen($prefix));
                // Extract numeric part (handle cases where there might be non-numeric characters)
                if (preg_match('/^(\d+)/', $numberPart, $matches)) {
                    $nextNumber = (int)$matches[1] + 1;
                }
            }
        }

        // Pad to minimum 6 digits
        $paddedNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        return $company->bill_id_prefix . $paddedNumber;
    }

    /**
     * Display a listing of bills with pagination support.
     * By default, only shows non-voided bills for the authenticated user's company.
     * Set `include_voided=true` to return only voided bills.
     *
     * @group Bills
     * @authenticated
     * @header Authorization Bearer {token}
     *
     * @queryParam per_page integer Number of items per page (default: 20). Example: 20
     * @queryParam page integer Page number (default: 1). Example: 1
     * @queryParam include_voided boolean If true, returns only voided bills. If false or not set, returns only active bills (default: false). Example: true
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "bill_code": "BILL000001",
     *       "date": "2025-12-10",
     *       "customer_info": {
     *         "name": "John Doe",
     *         "phone": "0123456789",
     *         "address": "123 Main St"
     *       }
     *     }
     *   ],
     *   "current_page": 1,
     *   "per_page": 20,
     *   "total": 100,
     *   "last_page": 5,
     *   "from": 1,
     *   "to": 20
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'message' => 'User does not have an associated company'
            ], 403);
        }

        $includeVoided = $request->boolean('include_voided', false);

        $query = Bill::where('company_id', $user->company_id);

        // If include_voided is true, return only voided bills
        // Otherwise, return only active bills (default behavior)
        if ($includeVoided) {
            $query->onlyTrashed();
        }

        $bills = $query->latest()->paginate($request->get('per_page', 20));

        // Transform the data to only include id, customer_info, and date
        $transformedBills = $bills->getCollection()->map(function ($bill) {
            return [
                'id' => $bill->id,
                'bill_code' => $bill->bill_code,
                'date' => $bill->date,
                'bus_datetime' => $bill->bus_datetime ? ($bill->bus_datetime instanceof \Carbon\Carbon ? $bill->bus_datetime->toISOString() : $bill->bus_datetime) : null,
                'customer_info' => $bill->customer_info ? (is_string($bill->customer_info) ? json_decode($bill->customer_info, true) : $bill->customer_info) : null,
            ];
        });

        // Return paginated response with transformed data
        return response()->json([
            'data' => $transformedBills,
            'current_page' => $bills->currentPage(),
            'per_page' => $bills->perPage(),
            'total' => $bills->total(),
            'last_page' => $bills->lastPage(),
            'from' => $bills->firstItem(),
            'to' => $bills->lastItem(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Bills
     * @authenticated
     * @header Authorization Bearer {token}
     *
     * @bodyParam date string required The bill date (Y-m-d format). Example: 2025-12-10
     * @bodyParam amount number required The bill amount. Example: 100.50
     * @bodyParam description string Optional description.
     * @bodyParam payment_method string Optional payment method (cash, bank_transfer, credit_card, e_wallet).
     * @bodyParam payment_date string Optional payment date (Y-m-d format).
     * @bodyParam customer_name string Optional customer name.
     * @bodyParam customer_phone string Optional customer phone.
     * @bodyParam customer_address string Optional customer address.
     * @bodyParam courier_policy_id integer Optional courier policy ID.
     * @bodyParam bus_datetime string Optional bus departure datetime (Y-m-d H:i:s format). Used for grouping bills by vehicle departure.
     * @bodyParam eta string Optional estimated time of arrival.
     * @bodyParam sst_rate number Optional SST rate percentage.
     * @bodyParam sst_amount number Optional SST amount.
     * @bodyParam media_attachment file Optional Single image file (max 5MB). Accepted formats: jpg, jpeg, png, gif, webp.
     *
     * @response 201 {
     *   "message": "Bill created successfully",
     *   "data": {
     *     "id": 1,
     *     "bill_code": "BILL000001",
     *     ...
     *   }
     * }
     * @response 403 {
     *   "message": "User does not have an associated company"
     * }
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {...}
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'message' => 'User does not have an associated company'
            ], 403);
        }

        $data = $request->validate([
            'date' => 'required|date',
            'bus_datetime' => 'nullable|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'courier_policy_id' => [
                'nullable',
                Rule::exists('courier_policies', 'id')->where(function($q) use ($user) {
                    return $q->where('company_id', $user->company_id);
                })
            ],
            'eta' => 'nullable|string',
            'sst_rate' => 'nullable|numeric',
            'sst_amount' => 'nullable|numeric',
            'media_attachment' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // Max 5MB
        ]);

        // Automatically set company_id from authenticated user
        $data['company_id'] = $user->company_id;

        // Auto-generate bill code using company prefix and running number
        try {
            $data['bill_code'] = $this->generateNextBillCode($data['company_id']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }

        // Ensure bill_code is unique (in case of race condition)
        while (Bill::where('bill_code', $data['bill_code'])->exists()) {
            // If collision occurs, increment and try again
            $company = Company::find($data['company_id']);
            $latestBill = Bill::where('company_id', $data['company_id'])
                ->orderBy('id', 'desc')
                ->first();

            if (!$latestBill) {
                break;
            }

            $prefix = $company->bill_id_prefix;
            $numberPart = substr($latestBill->bill_code, strlen($prefix));
            if (preg_match('/^(\d+)/', $numberPart, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
                $paddedNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
                $data['bill_code'] = $prefix . $paddedNumber;
            } else {
                break; // Safety break
            }
        }

        // Build payment_details JSON
        if ($request->payment_method || $request->payment_date) {
            $data['payment_details'] = json_encode([
                'method' => $request->payment_method,
                'date' => $request->payment_date,
            ]);
        }

        // Build customer_info JSON
        if ($request->customer_name || $request->customer_phone || $request->customer_address) {
            $data['customer_info'] = json_encode([
                'name' => $request->customer_name,
                'phone' => $request->customer_phone,
                'address' => $request->customer_address,
            ]);
        }

        // Build sst_details JSON
        if ($request->sst_rate || $request->sst_amount) {
            $data['sst_details'] = json_encode([
                'rate' => $request->sst_rate,
                'amount' => $request->sst_amount,
            ]);
        }

        // Auto-select company's policy if not provided
        if (empty($data['courier_policy_id']) && $data['company_id']) {
            $autoPolicy = \App\Models\CourierPolicy::where('company_id', $data['company_id'])->orderBy('id')->first();
            if ($autoPolicy) {
                $data['courier_policy_id'] = $autoPolicy->id;
            }
        }

        // Snapshot policy into bill
        if (!empty($data['courier_policy_id'])) {
            $policy = \App\Models\CourierPolicy::find($data['courier_policy_id']);
            if ($policy) {
                $data['policy_snapshot'] = json_encode([
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'company_id' => $policy->company_id,
                    'company_name' => optional($policy->company)->name,
                ]);
            }
        }

        // Handle media attachment upload
        if ($request->hasFile('media_attachment')) {
            $file = $request->file('media_attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('bills', $filename, 'public');
            $data['media_attachment'] = $path;
        }

        $bill = Bill::create($data);

        return response()->json([
            'message' => 'Bill created successfully',
            'data' => $bill->load('company', 'courierPolicy')
        ], 201);
    }

    /**
     * Display the specified bill.
     * Only shows non-voided bills for the authenticated user's company.
     *
     * @group Bills
     * @authenticated
     * @header Authorization Bearer {token}
     *
     * @param int $id Bill ID
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "bill_code": "BILL000001",
     *     "date": "2025-12-10",
     *     "amount": 3000.00,
     *     "description": null,
     *     "payment_details": {
     *       "method": "cash",
     *       "date": "2025-12-10"
     *     },
     *     "customer_info": {
     *       "name": "John Doe",
     *       "phone": "+60123456789",
     *       "address": "123 Main St"
     *     },
     *     "eta": "3",
     *     "sst_details": null,
     *     "media_attachment_url": "http://example.com/storage/bills/image.png",
     *     "company": {
     *       "id": 1,
     *       "name": "Company Name"
     *     },
     *     "courier_policy": null,
     *     "created_at": "2025-12-10T02:06:54.000000Z",
     *     "updated_at": "2025-12-10T03:01:06.000000Z"
     *   }
     * }
     * @response 403 {
     *   "message": "User does not have an associated company"
     * }
     * @response 404 {
     *   "message": "Bill not found"
     * }
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'message' => 'User does not have an associated company'
            ], 403);
        }

        $bill = Bill::where('id', $id)
            ->where('company_id', $user->company_id)
            ->first();

        if (!$bill) {
            return response()->json([
                'message' => 'Bill not found'
            ], 404);
        }

        $bill->load('company', 'courierPolicy');

        // Parse JSON fields for cleaner response
        $paymentDetails = null;
        if ($bill->payment_details) {
            $paymentDetails = is_string($bill->payment_details)
                ? json_decode($bill->payment_details, true)
                : $bill->payment_details;
        }

        $customerInfo = null;
        if ($bill->customer_info) {
            $customerInfo = is_string($bill->customer_info)
                ? json_decode($bill->customer_info, true)
                : $bill->customer_info;
        }

        $sstDetails = null;
        if ($bill->sst_details) {
            $sstDetails = is_string($bill->sst_details)
                ? json_decode($bill->sst_details, true)
                : $bill->sst_details;
        }

        // Build refined response
        $response = [
            'id' => $bill->id,
            'bill_code' => $bill->bill_code,
            'date' => $bill->date ? ($bill->date instanceof \Carbon\Carbon ? $bill->date->format('Y-m-d') : $bill->date) : null,
            'bus_datetime' => $bill->bus_datetime ? ($bill->bus_datetime instanceof \Carbon\Carbon ? $bill->bus_datetime->toISOString() : $bill->bus_datetime) : null,
            'amount' => (float) $bill->amount,
            'description' => $bill->description,
            'payment_details' => $paymentDetails,
            'customer_info' => $customerInfo,
            'eta' => $bill->eta,
            'sst_details' => $sstDetails,
            'media_attachment_url' => $bill->media_attachment
                ? URL::to(Storage::url($bill->media_attachment))
                : null,
            'company' => $bill->company ? [
                'id' => $bill->company->id,
                'name' => $bill->company->name,
            ] : null,
            'courier_policy' => $bill->courierPolicy ? [
                'id' => $bill->courierPolicy->id,
                'name' => $bill->courierPolicy->name,
            ] : null,
            'created_at' => $bill->created_at ? $bill->created_at->toISOString() : null,
            'updated_at' => $bill->updated_at ? $bill->updated_at->toISOString() : null,
        ];

        return response()->json([
            'data' => $response
        ]);
    }

    /**
     * Void (soft delete) the specified bill.
     * Once voided, the bill will not appear in index or show endpoints.
     * To correct a mistake, void the bill and create a new one.
     *
     * @group Bills
     * @authenticated
     * @header Authorization Bearer {token}
     *
     * @param int $id Bill ID
     * @response 200 {
     *   "message": "Bill voided successfully"
     * }
     * @response 400 {
     *   "message": "Bill is already voided"
     * }
     * @response 403 {
     *   "message": "User does not have an associated company"
     * }
     * @response 404 {
     *   "message": "Bill not found"
     * }
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'message' => 'User does not have an associated company'
            ], 403);
        }

        $bill = Bill::where('id', $id)
            ->where('company_id', $user->company_id)
            ->first();

        if (!$bill) {
            return response()->json([
                'message' => 'Bill not found'
            ], 404);
        }

        // Check if bill is already voided
        if ($bill->trashed()) {
            return response()->json([
                'message' => 'Bill is already voided'
            ], 400);
        }

        $bill->delete();

        return response()->json([
            'message' => 'Bill voided successfully'
        ]);
    }
}
