<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * Met à jour le profil de l'utilisateur.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function modifyProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|min:2|max:255',
            'prenom' => 'required|string|min:2|max:255',
            'date_naissance' => 'required|date_format:Y-m-d|before:today',
            'sexe' => 'required|in:M,F',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'tel' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        $user->email = $request->email;
        $user->save();

        $personne = $user->personne;

        if (!$personne) {
            return response()->json(['message' => 'Aucune information de personne associée à cet utilisateur.'], 404);
        }

        $personne->update([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'date_naissance' => $request->date_naissance,
            'sexe' => $request->sexe,
            'tel' => $request->tel,
        ]);

        return response()->json(['message' => 'Profil mis à jour avec succès.', 'user' => $user->load("personne")], 200);
    }

    /**
     * Modifie le mot de passe de l'utilisateur.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function modifyPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Le mot de passe actuel est incorrect.'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Mot de passe modifié avec succès.'], 200);
    }
}
