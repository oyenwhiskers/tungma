<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusDepartures;

class BusDeparturesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        // Admin: only see bus departures for their own company
        if ($user && $user->role === 'admin') {
            $busDepartures = BusDepartures::where('company_id', $user->company_id)->get();
        } else {
            // Super admin or other roles: see all
            $busDepartures = BusDepartures::all();
        }

        return view('bus_departures.index', compact('busDepartures'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('bus_departures.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $companyId = auth()->user()->role === 'admin' ? auth()->user()->company_id : $request->company_id;
        $data = $request->validate([
            'departure_time' => [
                'required',
                \Illuminate\Validation\Rule::unique('bus_departures')->withoutTrashed()->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })
            ],
            'company_id' => 'nullable|exists:companies,id',
        ], [
            'departure_time.unique' => 'This departure time already exists for this company.'
        ]);

        if (auth()->user()->role === 'admin') {
            $data['company_id'] = auth()->user()->company_id;
        }

        try {
            $trashed = BusDepartures::onlyTrashed()
                ->where('company_id', $data['company_id'])
                ->where('departure_time', $data['departure_time'])
                ->first();

            if ($trashed) {
                $trashed->restore();
                $trashed->update($data);
            } else {
                BusDepartures::create($data);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create bus departure. ' . $e->getMessage());
        }

        return redirect()->route('bus-departures.index')->with('success', 'Bus departure created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $busDeparture = BusDepartures::findOrFail($id);
        return view('bus_departures.edit', compact('busDeparture'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $busDeparture = BusDepartures::findOrFail($id);
        
        $companyId = auth()->user()->role === 'admin' ? auth()->user()->company_id : $request->company_id;
        $data = $request->validate([
            'departure_time' => [
                'required',
                \Illuminate\Validation\Rule::unique('bus_departures')->withoutTrashed()->ignore($busDeparture->id)->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })
            ],
            'company_id' => 'nullable|exists:companies,id',
        ], [
            'departure_time.unique' => 'This departure time already exists for this company.'
        ]);

        try {
            $busDeparture->update($data);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update bus departure. ' . $e->getMessage());
        }

        return redirect()->route('bus-departures.index')->with('success', 'Bus departure updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $busDeparture = BusDepartures::findOrFail($id);
        $busDeparture->delete();
        return redirect()->route('bus-departures.index');
    }
}
