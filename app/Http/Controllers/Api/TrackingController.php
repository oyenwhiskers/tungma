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
     *     "status": "in_transit",
     *     "delivery_date": "2025-12-15",
     *     "checked_by": "Alice Johnson"
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

        $response = [
            'bill_code' => $bill->bill_code,
            'status' => $bill->status,
            'delivery_date' => $estimatedDeliveryDate,
            'checked_by' => $bill->checker ? [
                'id' => $bill->checker->id,
                'name' => $bill->checker->name,
            ] : null,
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