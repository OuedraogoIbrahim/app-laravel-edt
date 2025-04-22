<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use Illuminate\Http\Request;

class MatieresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $matieres = Matiere::query()->get();
        return response()->json($matieres);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nombre_heures' => 'required|integer',
            'heures_utilisees' => 'required|integer',
            'periode' => 'required|string',
            'niveau_id' => 'required|exists:niveaux,id',
        ]);

        $matiere = Matiere::create($validatedData);

        return response()->json($matiere, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $matiere = Matiere::findOrFail($id);
        return response()->json($matiere);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $matiere = Matiere::findOrFail($id);

        $validatedData = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'nombre_heures' => 'sometimes|required|integer',
            'heures_utilisees' => 'sometimes|required|integer',
            'periode' => 'sometimes|required|string',
            'niveau_id' => 'sometimes|required|exists:niveaux,id',
        ]);

        $matiere->update($validatedData);

        return response()->json($matiere);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $matiere = Matiere::findOrFail($id);
        $matiere->delete();

        return response()->json(null, 204);
    }
}
