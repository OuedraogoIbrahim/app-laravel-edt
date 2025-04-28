<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use App\Models\Personne;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnseignantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $personnes = Personne::query()->where('role', 'enseignant')->get();
        return response()->json($personnes->load(['user', 'enseignant.matieres', 'enseignant.matieres.niveau']), 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'tel' => 'required|string|max:20',
            'matiere_ids' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $user = User::create([
            'email' => $validated['email'],
            'password' => bcrypt('password'),
        ]);

        $personne = new Personne();

        $personne->nom = $validated['nom'];
        $personne->prenom = $validated['prenom'];
        $personne->date_naissance = $validated['date_naissance'];
        $personne->sexe = $validated['sexe'];
        $personne->tel = $validated['tel'];
        $personne->role = 'enseignant';
        $personne->user_id = $user->id;
        $personne->save();

        $enseignant = new Enseignant();
        $enseignant->personne_id = $personne->id;
        $enseignant->save();

        $enseignant->matieres()->attach($validated['matiere_ids']);

        return response()->json($personne->load(['enseignant.matieres', 'enseignant.matieres.niveau']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $enseignant = Personne::query()
            ->where('role', 'enseignant')
            ->where('id', $id)
            ->first();

        if (!$enseignant) {
            return response()->json(['message' => 'Enseignant non trouvé'], 404);
        }

        return response()->json($enseignant->load(['matieres', 'matieres.niveau']), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $personne = Personne::query()
            ->where('role', 'enseignant')
            ->where('id', $id)
            ->first();

        if (!$personne) {
            return response()->json(['message' => 'Enseignant non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $personne->user_id,
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'tel' => 'required|string|max:20',
            'matiere_ids' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        User::where('id', $personne->user_id)->update([
            'email' => $validated['email'],
        ]);


        $personne->update([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'date_naissance' => $validated['date_naissance'],
            'sexe' => $validated['sexe'],
            'tel' => $validated['tel'],
        ]);

        $enseignant = Enseignant::query()->where('personne_id', $id)->first();

        $enseignant->matieres()->sync($validated['matiere_ids']);


        return response()->json($personne->load(['enseignant.matieres', 'enseignant.matieres.niveau']), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $personne = Personne::query()
            ->where('role', 'enseignant')
            ->where('id', $id)
            ->first();

        if (!$personne) {
            return response()->json(['message' => 'Enseignant non trouvé'], 404);
        }

        $user = $personne->user;
        $user->delete();

        return response()->json(['message' => 'Enseignant supprimé avec succès'], 200);
    }
}
