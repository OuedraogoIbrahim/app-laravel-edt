<?php

namespace App\Http\Controllers;

use App\Models\Niveau;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NiveauController extends Controller
{
    public function index()
    {
        $niveaux = Niveau::query()->with('filiere')->get();
        return response()->json($niveaux);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string|min:10',
            'filiere_id' => 'required|exists:filieres,id',
        ]);

        $niveau = Niveau::create($validatedData);
        $niveau->load('filiere');
        return response()->json($niveau, 201);
    }

    public function show(Niveau $niveau)
    {
        return response()->json($niveau);
    }

    public function update(Request $request, Niveau $niveau)
    {
        $validatedData = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string|min:10',
            'filiere_id' => 'sometimes|required|exists:filieres,id',
        ]);

        $niveau->update($validatedData);
        $niveau->load('filiere');
        return response()->json($niveau);
    }

    public function destroy(Niveau $niveau)
    {
        // Vérifier si le niveau a des matières associées (si applicable)
        if ($niveau->matieres()->exists()) {
            throw ValidationException::withMessages([
                'niveau' => ['Impossible de supprimer un niveau avec des matières associées.'],
            ]);
        }

        $niveau->delete();
        return response()->json(null, 204);
    }
}
