<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Company;
use App\Models\CourierPolicy;
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
        $latestBill = Bill::withTrashed()->where('company_id', $companyId)
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
                    $nextNumber = (int) $matches[1] + 1;
                }
            }
        }

        // Pad to minimum 6 digits
        $paddedNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        return $company->bill_id_prefix . $paddedNumber;
    }

    /**
     * List Bills
     *
     * Display a listing of bills with pagination support.
     *
     * @group Bills
     * @authenticated
     * @header Authorization Bearer {token}
     *
     * @queryParam search string Search by bill code or description. Example: INV-001
     * @queryParam payment_status string Filter by payment status ('paid', 'unpaid'). Example: unpaid
     * @queryParam collected_status string Filter by collected status ('collected', 'uncollected'). Example: uncollected
     * @queryParam date string Filter by date (Y-m-d). Example: 2025-12-15
     * @queryParam payment_method string Filter by payment method (cash, bank_transfer, etc.). Example: cash
     * @queryParam per_page integer Number of items per page (default: 20). Example: 20
     * @queryParam include_voided boolean If true, returns only voided bills. Example: false
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "bill_code": "INV-0001",
     *       "date": "2025-12-15",
     *       "amount": 150.00,
     *       "description": "Delivery fee",
     *       "is_paid": false,
     *       "is_collected": false
     *     }
     *   ],
     *   "current_page": 1,
     *   "per_page": 20,
     *   "total": 50,
     *   "last_page": 3,
     *   "from": 1,
     *   "to": 20
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'User does not have an associated company'], 403);
        }

        // Strictly scope to user's company
        $query = Bill::where('company_id', $user->company_id);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'paid') {
                $query->where('is_paid', true);
            } elseif ($request->payment_status === 'unpaid') {
                $query->where('is_paid', false);
            }
        }

        // Filter by collected status
        if ($request->filled('collected_status')) {
            if ($request->collected_status === 'collected') {
                $query->where('is_collected', true);
            } elseif ($request->collected_status === 'uncollected') {
                $query->where('is_collected', false);
            }
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_details->method', $request->payment_method);
        }

        // Include voided bills check
        if ($request->boolean('include_voided')) {
            $query->onlyTrashed();
        }

        // Eager load relationships
        $bills = $query->with(['company', 'checker', 'creator', 'fromCompany', 'toCompany', 'busDeparture'])
            ->latest()
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        // Transform the data
        $transformedBills = $bills->getCollection()->map(function ($bill) {
            return $this->transformBill($bill);
        });

        // Return paginated response
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
     * Create Bill
     *
     * Store a newly created bill.
     *
     * @group Bills
     * @authenticated
     * @header Authorization Bearer {token}
     *
     * @bodyParam date string required The date of the bill (Y-m-d). Example: 2025-12-15
     * @bodyParam amount number required The bill amount. Example: 120.50
     * @bodyParam bus_departures_id integer Optional bus departure ID. Example: 5
     * @bodyParam description string Optional description of the bill. Example: Delivery charge for electronics
     * @bodyParam payment_method string Optional payment method (e.g., cash, credit_card). Example: cash
     * @bodyParam payment_date string Optional payment date (Y-m-d). Example: 2025-12-16
     * @bodyParam from_company_id integer Optional sender company ID. Example: 2
     * @bodyParam to_company_id integer Optional receiver company ID. Example: 3
     * @bodyParam sender_name string Optional name of the sender. Example: John Doe
     * @bodyParam sender_phone string Optional phone number of the sender. Example: 0123456789
     * @bodyParam receiver_name string Optional name of the receiver. Example: Jane Smith
     * @bodyParam receiver_phone string Optional phone number of the receiver. Example: 0198765432
     * @bodyParam courier_policy_id integer Optional courier policy ID. If not provided, it will auto-select the company's default policy. Example: 1
     * @bodyParam sst_rate number Optional SST rate percentage. Example: 6
     * @bodyParam sst_amount number Optional SST amount calculated. Example: 7.23
     * @bodyParam media_attachment file Optional image attachment (jpeg, jpg, png, gif, webp). Max 5MB.
     * @bodyParam payment_proof_attachment file Optional payment proof document (image or pdf). Max 5MB.
     * @bodyParam is_paid boolean Optional payment status. Example: false
     * @bodyParam is_collected boolean Optional collection status. Example: false
     * @bodyParam checked_by integer Optional ID of the user who checked/verified the bill. Example: 2
     *
     * @response 201 {
     *   "message": "Bill created successfully",
     *   "data": {
     *     "id": 15,
     *     "bill_code": "INV-000015",
     *     "date": "2025-12-15",
     *     "amount": 120.50,
     *     ...
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "amount": ["The amount field is required."]
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'User does not have an associated company'], 403);
        }

        // Force company_id to authenticated user's company
        $request->merge(['company_id' => $user->company_id]);

        $data = $request->validate([
            'date' => 'required|date',
            'bus_departures_id' => 'nullable|exists:bus_departures,id',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'from_company_id' => 'nullable|exists:companies,id',
            'to_company_id' => 'nullable|exists:companies,id',
            'sender_name' => 'nullable|string',
            'sender_phone' => 'nullable|string',
            'receiver_name' => 'nullable|string',
            'receiver_phone' => 'nullable|string',
            'courier_policy_id' => [
                'nullable',
                Rule::exists('courier_policies', 'id')->where(function ($q) use ($request) {
                    return $q->where('company_id', $request->company_id);
                })
            ],
            'company_id' => 'required|exists:companies,id',
            'sst_rate' => 'nullable|numeric',
            'sst_amount' => 'nullable|numeric',
            'media_attachment' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // Max 5MB
            'payment_proof_attachment' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120',
            'is_paid' => 'nullable|boolean',
            'is_collected' => 'nullable|boolean',
            'checked_by' => 'nullable|exists:users,id',
        ]);

        // Auto-generate bill code using company prefix and running number
        try {
            $data['bill_code'] = $this->generateNextBillCode($data['company_id']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        // Ensure bill_code is unique
        while (Bill::where('bill_code', $data['bill_code'])->exists()) {
            $company = Company::find($data['company_id']);
            $latestBill = Bill::where('company_id', $data['company_id'])
                ->orderBy('id', 'desc')
                ->first();

            $prefix = $company->bill_id_prefix;
            $numberPart = substr($latestBill->bill_code, strlen($prefix));
            if (preg_match('/^(\d+)/', $numberPart, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
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

        // Build sst_details JSON
        if ($request->sst_rate || $request->sst_amount) {
            $data['sst_details'] = json_encode([
                'rate' => $request->sst_rate,
                'amount' => $request->sst_amount,
            ]);
        }

        // Auto-select company's policy if not provided
        if (empty($data['courier_policy_id']) && $request->company_id) {
            $autoPolicy = CourierPolicy::where('company_id', $request->company_id)->orderBy('id')->first();
            if ($autoPolicy) {
                $data['courier_policy_id'] = $autoPolicy->id;
            }
        }

        // Snapshot policy into bill
        if (!empty($data['courier_policy_id'])) {
            $policy = CourierPolicy::find($data['courier_policy_id']);
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
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('bills', $filename, 'public');
            $data['media_attachment'] = $path;
        }

        // Handle payment proof attachment upload
        if ($request->hasFile('payment_proof_attachment')) {
            $file = $request->file('payment_proof_attachment');
            $filename = time() . '_proof_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('bills', $filename, 'public');
            $data['payment_proof_attachment'] = $path;
        }

        // Set created_by to current authenticated user
        $data['created_by'] = auth()->id();
        $data['status'] = 'In_transit';

        // Handle is_paid
        if (isset($data['is_paid'])) {
            $data['is_paid'] = filter_var($data['is_paid'], FILTER_VALIDATE_BOOLEAN);
        } else {
            $data['is_paid'] = false;
        }

        // Handle is_collected
        if (isset($data['is_collected'])) {
            $data['is_collected'] = filter_var($data['is_collected'], FILTER_VALIDATE_BOOLEAN);
        } else {
            $data['is_collected'] = false;
        }

        // Handle bus_departures_id
        if (isset($data['bus_departures_id']) && $data['bus_departures_id'] === '') {
            $data['bus_departures_id'] = null;
        }

        $bill = Bill::create($data);

        // Generate PDF immediately (Sync) because it is now fast enough (~2s)
        \App\Jobs\GenerateBillPdf::dispatchSync($bill);
        
        // Reload to get the pdf_url
        $bill->refresh();

        return response()->json([
            'message' => 'Bill created successfully',
            'data' => $this->transformBill($bill)
        ], 201);
    }

    /**
     * Show Bill
     *
     * Display the specified bill.
     *
     * @group Bills
     * @authenticated
     * @urlParam id integer required The ID of the bill. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "bill_code": "INV-001",
     *     "date": "2025-12-15",
     *     "amount": 100.00,
     *     "description": "Delivery fee",
     *     "payment_details": {"method": "cash", "date": "2025-12-16"},
     *     "from_company_id": 2,
     *     "to_company_id": 3,
     *     "sender_name": "John Doe",
     *     "sender_phone": "0123456789",
     *     "receiver_name": "Jane Smith",
     *     "receiver_phone": "0198765432",
     *     "courier_policy_id": 1,
     *     "company_id": 1,
     *     "sst_details": {"rate": 6, "amount": 6.00},
     *     "policy_snapshot": {"id": 1, "name": "Standard", "company_id": 1},
     *     "media_attachment": "bills/image.jpg",
     *     "payment_proof_attachment": "bills/proof.pdf",
     *     "is_paid": true,
     *     "is_collected": false,
     *     "status": "In_transit",
     *     "created_by": 1,
     *     "checked_by": null,
     *     "bus_departures_id": 5,
     *     "created_at": "2025-12-15T10:00:00.000000Z",
     *     "updated_at": "2025-12-15T10:00:00.000000Z",
     *     "deleted_at": null,
     *     "departure_time": "10:00:00",
     *     "media_attachment_url": "http://localhost/storage/bills/image.jpg",
     *     "payment_proof_attachment_url": "http://localhost/storage/bills/proof.pdf",
     *     "from_company": {"id": 2, "name": "Sender Corp"},
     *     "to_company": {"id": 3, "name": "Receiver Corp"},
     *     "company": {
     *       "id": 1,
     *       "name": "My Company",
     *       "contact_number": "0123456789",
     *       "address": "123 Main St",
     *       "email": "admin@mycompany.com",
     *       "based_in": "Kuala Lumpur",
     *       "registration_number": "123456-X",
     *       "sst_number": "W10-2345-5678",
     *       "bill_id_prefix": "INV",
     *       "created_at": "2023-01-01T00:00:00.000000Z",
     *       "updated_at": "2023-01-01T00:00:00.000000Z",
     *       "deleted_at": null
     *     },
     *     "courier_policy": {
     *       "id": 1,
     *       "name": "Standard",
     *       "description": "Standard delivery policy",
     *       "company_id": 1,
     *       "created_at": "2023-01-01T00:00:00.000000Z",
     *       "updated_at": "2023-01-01T00:00:00.000000Z",
     *       "deleted_at": null
     *     },
     *     "creator": {"id": 1, "name": "Admin User"},
     *     "checker": null
     *   }
     * }
     * @response 404 {
     *   "message": "Bill not found"
     * }
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'User does not have an associated company'], 403);
        }

        // Strictly scope to user's company and include soft-deleted bills
        $bill = Bill::withTrashed()
            ->where('company_id', $user->company_id)
            ->where('id', $id)
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'Bill not found'], 404);
        }

        $bill->load('fromCompany', 'toCompany', 'busDeparture', 'company', 'courierPolicy', 'creator', 'checker');

        return response()->json([
            'data' => array_merge($bill->attributesToArray(), $this->transformBill($bill))
        ]);
    }

    /**
     * Update Bill
     *
     * Update the specified bill.
     *
     * @group Bills
     * @authenticated
     * @urlParam id integer required The ID of the bill. Example: 1
     *
     * @bodyParam bill_code string required The bill code (must be unique). Example: INV-000001
     * @bodyParam date string required The date of the bill (Y-m-d). Example: 2025-12-15
     * @bodyParam amount number required The bill amount. Example: 120.50
     * @bodyParam bus_departures_id integer Optional bus departure ID. Example: 5
     * @bodyParam description string Optional description. Example: Update delivery fee
     * @bodyParam payment_method string Optional payment method. Example: cash
     * @bodyParam payment_date string Optional payment date (Y-m-d). Example: 2025-12-16
     * @bodyParam from_company_id integer Optional sender company ID. Example: 2
     * @bodyParam to_company_id integer Optional receiver company ID. Example: 3
     * @bodyParam sender_name string Optional name of the sender. Example: John Doe
     * @bodyParam sender_phone string Optional phone number of the sender. Example: 0123456789
     * @bodyParam receiver_name string Optional name of the receiver. Example: Jane Smith
     * @bodyParam receiver_phone string Optional phone number of the receiver. Example: 0198765432
     * @bodyParam courier_policy_id integer Optional courier policy ID. Example: 1
     * @bodyParam sst_rate number Optional SST rate percentage. Example: 6
     * @bodyParam sst_amount number Optional SST amount. Example: 7.23
     * @bodyParam media_attachment file Optional image attachment (jpeg, jpg, png, gif, webp). Max 5MB.
     * @bodyParam payment_proof_attachment file Optional payment proof document (image or pdf). Max 5MB.
     * @bodyParam is_paid boolean Optional payment status. Example: true
     * @bodyParam is_collected boolean Optional collection status. Example: true
     * @bodyParam checked_by integer Optional ID of the user who checked/verified the bill. Example: 2
     *
     * @response 200 {
     *   "message": "Bill updated successfully",
     *   "data": {
     *     "id": 1,
     *     "bill_code": "INV-000001",
     *     ...
     *   }
     * }
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'User does not have an associated company'], 403);
        }

        // Strictly scope to user's company (can only edit active bills usually, but failOrFail is standard)
        $bill = Bill::where('company_id', $user->company_id)->findOrFail($id);

        // Force company_id
        $request->merge(['company_id' => $user->company_id]);

        $data = $request->validate([
            'bill_code' => 'required|string|max:255|unique:bills,bill_code,' . $bill->id,
            'date' => 'required|date',
            'bus_departures_id' => 'nullable|exists:bus_departures,id',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'from_company_id' => 'nullable|exists:companies,id',
            'to_company_id' => 'nullable|exists:companies,id',
            'sender_name' => 'nullable|string',
            'sender_phone' => 'nullable|string',
            'receiver_name' => 'nullable|string',
            'receiver_phone' => 'nullable|string',
            'courier_policy_id' => [
                'nullable',
                Rule::exists('courier_policies', 'id')->where(function ($q) use ($request) {
                    return $q->where('company_id', $request->company_id);
                })
            ],
            'company_id' => 'required|exists:companies,id',
            'sst_rate' => 'nullable|numeric',
            'sst_amount' => 'nullable|numeric',
            'media_attachment' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'payment_proof_attachment' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120',
            'is_paid' => 'nullable|boolean',
            'is_collected' => 'nullable|boolean',
            'checked_by' => 'nullable|exists:users,id',
        ]);

        // Build payment_details JSON
        if ($request->payment_method || $request->payment_date) {
            $data['payment_details'] = json_encode([
                'method' => $request->payment_method,
                'date' => $request->payment_date,
            ]);
        }

        // Build sst_details JSON
        if ($request->sst_rate || $request->sst_amount) {
            $data['sst_details'] = json_encode([
                'rate' => $request->sst_rate,
                'amount' => $request->sst_amount,
            ]);
        }

        // If company changed and policy no longer matches, auto-adjust
        if (!empty($data['courier_policy_id'])) {
            $policy = CourierPolicy::find($data['courier_policy_id']);
            if (!$policy || $policy->company_id != $data['company_id']) {
                $data['courier_policy_id'] = null;
            }
        }
        if (empty($data['courier_policy_id']) && $data['company_id']) {
            $autoPolicy = CourierPolicy::where('company_id', $data['company_id'])->orderBy('id')->first();
            if ($autoPolicy) {
                $data['courier_policy_id'] = $autoPolicy->id;
            }
        }

        // Refresh snapshot
        if (!empty($data['courier_policy_id'])) {
            $policy = CourierPolicy::find($data['courier_policy_id']);
            if ($policy) {
                $data['policy_snapshot'] = json_encode([
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'company_id' => $policy->company_id,
                    'company_name' => optional($policy->company)->name,
                ]);
            }
        } else {
            $data['policy_snapshot'] = null;
        }

        // Handle media attachment
        if ($request->hasFile('media_attachment')) {
            if ($bill->media_attachment && Storage::disk('public')->exists($bill->media_attachment)) {
                Storage::disk('public')->delete($bill->media_attachment);
            }
            $file = $request->file('media_attachment');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('bills', $filename, 'public');
            $data['media_attachment'] = $path;
        }

        // Handle payment proof attachment
        if ($request->hasFile('payment_proof_attachment')) {
            if ($bill->payment_proof_attachment && Storage::disk('public')->exists($bill->payment_proof_attachment)) {
                Storage::disk('public')->delete($bill->payment_proof_attachment);
            }
            $file = $request->file('payment_proof_attachment');
            $filename = time() . '_proof_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('bills', $filename, 'public');
            $data['payment_proof_attachment'] = $path;
        }

        // Boolean conversions
        if (isset($data['is_paid'])) {
            $data['is_paid'] = filter_var($data['is_paid'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($data['is_collected'])) {
            $data['is_collected'] = filter_var($data['is_collected'], FILTER_VALIDATE_BOOLEAN);
        }

        // Nullable fields
        if (isset($data['checked_by']) && $data['checked_by'] === '') {
            $data['checked_by'] = null;
        }
        if (isset($data['bus_departures_id']) && $data['bus_departures_id'] === '') {
            $data['bus_departures_id'] = null;
        }

        // Set status based on check
        $checkedByValue = array_key_exists('checked_by', $data) ? $data['checked_by'] : $bill->checked_by;
        $data['status'] = $checkedByValue ? 'Arrived' : 'In_transit';

        $bill->update($data);

        // Generate PDF immediately (Sync)
        \App\Jobs\GenerateBillPdf::dispatchSync($bill);
        
        $bill->refresh();

        return response()->json([
            'message' => 'Bill updated successfully',
            'data' => $this->transformBill($bill)
        ]);
    }

    /**
     * Delete Bill
     *
     * Void (soft delete) the specified bill.
     *
     * @group Bills
     * @authenticated
     * @urlParam id integer required The ID of the bill. Example: 1
     *
     * @response 200 {
     *   "message": "Bill voided successfully"
     * }
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'User does not have an associated company'], 403);
        }

        $bill = Bill::where('company_id', $user->company_id)->findOrFail($id);

        $bill->delete();

        return response()->json(['message' => 'Bill voided successfully']);
    }

    /**
     * Download Bill PDF
     *
     * Generate bill template/receipt PDF.
     *
     * @group Bills
     * @authenticated
     * @urlParam id integer required The ID of the bill. Example: 1
     * @queryParam copy string Copy type: 'customer', 'office', 'receiver', 'book'. Default: customer. Example: customer
     *
     * @response 200 Binary PDF file
     */
    public function template(Request $request, $id)
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'User does not have an associated company'], 403);
        }

        $bill = Bill::withTrashed()
            ->where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();

        $bill->load('company', 'courierPolicy', 'fromCompany', 'toCompany', 'busDeparture');

        // Parse JSON fields
        $sstDetails = null;
        if ($bill->sst_details) {
            $sstDetails = is_string($bill->sst_details) ? json_decode($bill->sst_details, true) : $bill->sst_details;
        }

        $paymentDetails = null;
        if ($bill->payment_details) {
            $paymentDetails = is_string($bill->payment_details) ? json_decode($bill->payment_details, true) : $bill->payment_details;
        }

        $copyType = $request->get('copy', 'combined');
        $validCopyTypes = ['customer', 'office', 'receiver', 'book', 'combined'];
        if (!in_array($copyType, $validCopyTypes)) {
            $copyType = 'combined';
        }

        // Determine which template to use
        if ($copyType === 'combined') {
            // Use combined template (1 customer copy + 2 office copies)
            $templateView = 'bills.template-combined';
        } elseif ($copyType === 'office' || $copyType === 'receiver') {
            $templateView = 'bills.template-office';
        } else {
            $templateView = 'bills.template';
        }

        $isPdf = $request->get('format') !== 'html';

        if (!$isPdf) {
            return view($templateView, compact('bill', 'sstDetails', 'paymentDetails', 'copyType', 'isPdf'));
        }

        $pdf = \PDF::loadView($templateView, compact('bill', 'sstDetails', 'paymentDetails', 'copyType', 'isPdf'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('bill-' . $bill->bill_code . '-' . $copyType . '.pdf');
    }

    /**
     * Transform a bill model into an array response.
     */
    private function transformBill($bill)
    {
        $paymentDetails = null;
        if ($bill->payment_details) {
            $paymentDetails = is_string($bill->payment_details)
                ? json_decode($bill->payment_details, true)
                : $bill->payment_details;
        }

        $sstDetails = null;
        if ($bill->sst_details) {
            $sstDetails = is_string($bill->sst_details)
                ? json_decode($bill->sst_details, true)
                : $bill->sst_details;
        }

        return [
            'id' => $bill->id,
            'bill_code' => $bill->bill_code,
            'date' => $bill->date ? ($bill->date instanceof \Carbon\Carbon ? $bill->date->format('Y-m-d') : $bill->date) : null,
            'bus_departures_id' => $bill->bus_departures_id,
            'departure_time' => $bill->busDeparture?->departure_time,
            'amount' => (float) $bill->amount,
            'description' => $bill->description,
            'sender_name' => $bill->sender_name,
            'sender_phone' => $bill->sender_phone,
            'receiver_name' => $bill->receiver_name,
            'receiver_phone' => $bill->receiver_phone,
            'payment_details' => $paymentDetails,
            'is_paid' => (bool) $bill->is_paid,
            'is_collected' => (bool) $bill->is_collected,
            'sst_details' => $sstDetails,
            'media_attachment_url' => $bill->media_attachment
                ? URL::to(Storage::url($bill->media_attachment))
                : null,
            'pdf_url' => $bill->pdf_url,
            'payment_proof_attachment_url' => $bill->payment_proof_attachment
                ? URL::to(Storage::url($bill->payment_proof_attachment))
                : null,
            'from_company' => $bill->fromCompany ? [
                'id' => $bill->fromCompany->id,
                'name' => $bill->fromCompany->name,
            ] : null,
            'to_company' => $bill->toCompany ? [
                'id' => $bill->toCompany->id,
                'name' => $bill->toCompany->name,
            ] : null,
            'company' => $bill->company ? $bill->company->attributesToArray() : null,
            'courier_policy' => $bill->courierPolicy ? $bill->courierPolicy->attributesToArray() : null,
            'creator' => $bill->creator ? [
                'id' => $bill->creator->id,
                'name' => $bill->creator->name,
            ] : null,
            'checker' => $bill->checker ? [
                'id' => $bill->checker->id,
                'name' => $bill->checker->name,
            ] : null,
            'created_at' => $bill->created_at ? $bill->created_at->toISOString() : null,
            'updated_at' => $bill->updated_at ? $bill->updated_at->toISOString() : null,
        ];
    }
}
