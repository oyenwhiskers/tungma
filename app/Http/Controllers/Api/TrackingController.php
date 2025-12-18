<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class TrackingController extends Controller
{
    /**
     * Public tracking lookup by bill code.
     *
     * @group Public Tracking
     * @authenticated
     *
     * @urlParam bill_code string required The bill/tracking code. Example: BILL000123
     *
     * @response 200 {
     *   "data": {
     *     "bill_code": "BILL000123",
     *     "status": "In_transit",
     *     "estimated_delivery_iso": "2025-12-15T08:00:00.000000Z",
     *     "estimated_delivery_date": "2025-12-15",
     *     "eta_note": "3 days",
     *     "current_location": "Kuala Lumpur Hub",
     *     "amount": 150.5,
     *     "is_paid": false,
     *     "customer_ic_number": "900101-01-1234",
     *     "customer_info": {
     *       "name": "John Doe",
     *       "phone": "0123456789",
     *       "address": "123 Main St"
     *     },
     *     "sender": {
     *       "name": "Alice Sender",
     *       "phone": "012-2222222"
     *     },
     *     "receiver": {
     *       "name": "Bob Receiver",
     *       "phone": "013-3333333"
     *     },
     *     "routing": {
     *       "from_company": {
     *         "id": 1,
     *         "name": "Warehouse A",
     *         "address": "Origin address"
     *       },
     *       "to_company": {
     *         "id": 2,
     *         "name": "City B Branch",
     *         "address": "Destination address"
     *       },
     *       "owning_company": {
     *         "id": 3,
     *         "name": "Tung Ma Express",
     *         "contact_number": "+60123456789",
     *         "email": "ops@example.com"
     *       }
     *     },
     *     "contact": {
     *       "staff_in_charge": {
     *         "id": 10,
     *         "name": "Alice Johnson",
     *         "email": "alice@example.com",
     *         "contact_number": "+60123456780",
     *         "position": "Operations Lead"
     *       },
     *       "company": {
     *         "id": 3,
     *         "name": "Tung Ma Express",
     *         "contact_number": "+60123456789",
     *         "email": "ops@example.com"
     *       }
     *     },
     *     "attachments": {
     *       "media": "https://example.com/storage/bills/img.png",
     *       "payment_proof": "https://example.com/storage/bills/proof.pdf"
     *     },
     *     "courier_policy": {
     *       "id": 5,
     *       "name": "Standard Delivery"
     *     },
     *     "bus_departures_id": 1,
     *     "departure_time": "08:30:00",
     *     "created_at": "2025-12-10T02:06:54.000000Z",
     *     "updated_at": "2025-12-12T09:20:00.000000Z"
     *   }
     * }
     *
     * @response 404 {"message": "Bill not found"}
     */
    public function show(Request $request, string $billCode): JsonResponse
    {
        $normalizedCode = Str::lower($billCode);

        $bill = Bill::with(['company', 'fromCompany', 'toCompany', 'courierPolicy', 'creator', 'checker', 'busDeparture'])
            ->whereRaw('LOWER(bill_code) = ?', [$normalizedCode])
            ->first();

        if (! $bill) {
            return response()->json([
                'message' => 'Bill not found',
            ], 404);
        }

        // Parse JSON fields for clean output
        $paymentDetails = $this->decodeJsonField($bill->payment_details);
        $customerInfo = $this->decodeJsonField($bill->customer_info);
        $sstDetails = $this->decodeJsonField($bill->sst_details);

        $statusLabel = $bill->status
            ? ucwords(str_replace('_', ' ', $bill->status))
            : 'In Transit';

        // Combine bill date with bus departure time for estimated delivery
        $estimatedDeliveryIso = null;
        $estimatedDeliveryDate = null;
        if ($bill->date && $bill->busDeparture) {
            $datetime = \Carbon\Carbon::parse($bill->date->format('Y-m-d') . ' ' . $bill->busDeparture->departure_time);
            $estimatedDeliveryIso = $datetime->toISOString();
            $estimatedDeliveryDate = $datetime->toDateString();
        } elseif ($bill->date) {
            $estimatedDeliveryDate = $bill->date->toDateString();
        }

        // Prefer checker as staff-in-charge, otherwise fall back to creator
        $staff = $bill->checker ?: $bill->creator;
        $staffData = $staff ? [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'contact_number' => $staff->contact_number,
            'position' => $staff->position,
        ] : null;

        $currentLocation = $bill->toCompany->name
            ?? $bill->fromCompany->name
            ?? $bill->company->name
            ?? null;

        $response = [
            'bill_code' => $bill->bill_code,
            'status' => $bill->status,
            'delivery_date' => $estimatedDeliveryDate,
            'eta_note' => $bill->eta,
            'customer_ic_number' => $bill->customer_ic_number ?? ($customerInfo['ic'] ?? null),
            'customer_info' => $customerInfo,
            'sender' => [
                'name' => $bill->sender_name,
                'phone' => $bill->sender_phone,
            ],
            'receiver' => [
                'name' => $bill->receiver_name,
                'phone' => $bill->receiver_phone,
            ],
            'routing' => [
                'from_company' => $bill->fromCompany ? [
                    'id' => $bill->fromCompany->id,
                    'name' => $bill->fromCompany->name,
                    'address' => $bill->fromCompany->address,
                ] : null,
                'to_company' => $bill->toCompany ? [
                    'id' => $bill->toCompany->id,
                    'name' => $bill->toCompany->name,
                    'address' => $bill->toCompany->address,
                ] : null,
                'owning_company' => $bill->company ? [
                    'id' => $bill->company->id,
                    'name' => $bill->company->name,
                    'contact_number' => $bill->company->contact_number,
                    'email' => $bill->company->email,
                ] : null,
            ],
            'contact' => [
                'staff_in_charge' => $staffData,
                'company' => $bill->company ? [
                    'id' => $bill->company->id,
                    'name' => $bill->company->name,
                    'contact_number' => $bill->company->contact_number,
                    'email' => $bill->company->email,
                ] : null,
            ],
            'created_at' => $bill->created_at ? $bill->created_at->toISOString() : null,
            'updated_at' => $bill->updated_at ? $bill->updated_at->toISOString() : null,
        ];

        return response()->json([
            'data' => $response,
        ]);
    }

    /**
     * Safely decode JSON fields that may be stored as strings or arrays.
     */
    private function decodeJsonField(mixed $value): ?array
    {
        if (! $value) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}