<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use Illuminate\Http\Request;

class FiliereController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $filieres = Filiere::query()->with('niveaux')->get();
        return response()->json($filieres);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string|min:4',
        ]);

        $filiere = Filiere::create($validatedData);

        $filiere->load('niveaux');

        return response()->json($filiere, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Filiere $filiere)
    {
        $filiere->load('niveaux');
        return response()->json($filiere);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Filiere $filiere)
    {
        $validatedData = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string|min:4',
        ]);

        $filiere->update($validatedData);

        $filiere->load('niveaux');

        return response()->json($filiere);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Filiere $filiere)
    {
        $filiere->delete();

        return response()->json(null, 204);
    }
}
