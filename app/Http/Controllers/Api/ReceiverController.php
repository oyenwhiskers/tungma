<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Receiver;

class ReceiverController extends Controller
{
    /**
     * List Receivers
     *
     * @group Receivers
     * @authenticated
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Receiver::query();

        // If not super admin, only return receivers belonging to the user's company
        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
        } else if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%");
        }

        $receivers = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $receivers
        ]);
    }
}
