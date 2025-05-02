<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use Illuminate\Http\Request;

class ClassesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Salle::all(), 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacite' => 'required|integer',
        ]);

        $salle = Salle::create($request->all());

        return response()->json($salle, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $salle = Salle::findOrFail($id);
        return response()->json($salle);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacite' => 'required|integer',
        ]);

        $salle = Salle::findOrFail($id);
        $salle->update($request->all());

        return response()->json($salle);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $salle = Salle::findOrFail($id);
        $salle->delete();

        return response()->json(null, 204);
    }
}
