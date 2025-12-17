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
        $busDepartures = BusDepartures::all();
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
        BusDepartures::create($request->all());
        return redirect()->route('bus-departures.index');
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
        $busDeparture->update($request->all());
        return redirect()->route('bus-departures.index');
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
