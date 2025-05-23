<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Parant;
use App\Models\Personne;
use App\Models\User;
use Google_Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthentificationController extends Controller
{
    //

    protected $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $this->client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        // $this->client->addScope(['email', 'profile']);
    }

    public function register(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'filiere' => 'required|exists:filieres,id',
            'niveau' => 'required|exists:niveaux,id',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'tel' => 'required',
            'role' => 'required|in:admin,enseignant,etudiant,parent,responsable',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
            'expo_token' => 'nullable|string',
        ]);

        if ($valid->fails()) {
            return response()->json([
                'errors' => $valid->errors(),
            ], 422);
        }


        $validatedData = $valid->validated();

        // if (User::query()->where('expo_token', $validatedData['expo_token'])->exists()) {
        //     return response()->json([
        //         'errors' => [
        //             'expo_token' => ['Vous avez déjà un compte.'],
        //         ],
        //     ], 422);
        // }

        $user = new User();
        $user->email = $validatedData['email'];
        $user->password = bcrypt($validatedData['password']);
        $user->expo_token = $validatedData['expo_token'];
        $user->save();

        $personne = new Personne();
        $personne->nom = $validatedData['nom'];
        $personne->prenom = $validatedData['prenom'];
        $personne->date_naissance = $validatedData['date_naissance'];
        $personne->sexe = $validatedData['sexe'];
        $personne->tel = $validatedData['tel'];
        $personne->role = $validatedData['role'];
        $personne->user_id = $user->id;
        $personne->save();

        if ($validatedData['role'] == 'parent') {
            $parent = new Parant();
            $parent->filiere_id = $validatedData['filiere'];
            $parent->niveau_id = $validatedData['niveau'];
            $parent->personne_id = $personne->id;
        }

        if ($validatedData['role'] == 'etudiant') {
            $etudiant = new Etudiant();
            $etudiant->personne_id = $personne->id;
            $etudiant->filiere_id = $validatedData['filiere'];
            $etudiant->niveau_id = $validatedData['niveau'];
            $etudiant->save();
        }


        if ($user->personne->role === 'etudiant') {
            $user->load(['personne', 'personne.etudiant' => function ($query) {
                $query->with('filiere', 'niveau');
            }]);
        } else {
            // $user->load('personne');
            $user->load(['personne', 'personne.parent' => function ($query) {
                $query->with('filiere', 'niveau');
            }]);
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user
        ], 200);
    }


    public function login(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'expo_token' => 'nullable|string',
        ]);

        if ($valid->fails()) {
            return response()->json([
                'errors' => $valid->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        $user->expo_token = $request->expo_token;
        $user->update();


        if ($user->personne->role === 'enseignant') {
            $user->load(['personne', 'personne.enseignant', 'personne.enseignant.matieres', 'personne.enseignant.matieres.niveau', 'personne.enseignant.matieres.niveau.filiere']);
        } elseif ($user->personne->role === 'etudiant' || $user->personne->role === 'delegue') {
            $user->load(['personne', 'personne.etudiant' => function ($query) {
                $query->with('filiere', 'niveau');
            }]);
        } else {
            $user->load(['personne', 'personne.parent' => function ($query) {
                $query->with('filiere', 'niveau');
            }]);

            // $user->load('personne');
        }

        return response()->json([
            'token' => $request->device ?  $user->createToken('web')->plainTextToken : $user->createToken('mobile')->plainTextToken,
            'user' => $user,
        ], 200);
    }


    public function logout(Request $request)
    {
        Log::info(Auth::user());

        try {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Déconnecté avec succès']);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Une erreur est survenue'], 500);
        }
    }

    public function redirectToGoogle(Request $request)
    {
        if ($request->userId) {
            return Socialite::driver('google')->with(['state' => $request->userId])->stateless()->redirect();
        } else {
            return Socialite::driver('google')->stateless()->redirect();
        }
    }

    public function redirectToGithub(Request $request)
    {
        if ($request->userId) {
            return Socialite::driver('github')->stateless()->with(['state' => $request->userId])->redirect();
        } else {
            return Socialite::driver('github')->stateless()->redirect();
        }
    }

    public function redirectToFacebook(Request $request)
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }

    public function loginWithGoogle(Request $request)
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        if (!$googleUser) {
            return response()->json([
                'errors' => [
                    'token' => ['Token invalide']
                ]
            ], 401);
        }

        if ($request->state) {
            $user = User::query()->findOrFail($request->state);
            $user->provider = 'google';
            $user->provider_id = $googleUser->id;
            $user->update();

            $token = $user->createToken('google-token')->plainTextToken;
            $user = $user->load("personne");

            return redirect("http://localhost:5173/login/success?token=$token&user=$user");
        }

        $user = User::query()
            ->where('provider', 'google')
            ->where('provider_id', $googleUser->id)
            ->first();

        if (!$user) {
            return response()->json([
                'errors' => [
                    'credentials' => ['Informations invalides'],
                ]
            ], 401);
        }

        $token = $user->createToken('google-token')->plainTextToken;
        $user = $user->load("personne");

        return redirect("http://localhost:5173/login/success?token=$token" . "&user=$user");
    }

    public function loginWithGithub(Request $request)
    {
        $githubUser = Socialite::driver('github')->stateless()->user();
        if (!$githubUser) {
            return response()->json([
                'errors' => [
                    'token' => ['Token invalide']
                ]
            ], 401);
        }

        if ($request->state) {
            $user = User::query()->findOrFail($request->state);
            $user->provider = 'github';
            $user->provider_id = $githubUser->id;
            $user->update();

            $token = $user->createToken('github-token')->plainTextToken;
            $user = $user->load("personne");

            return redirect("http://localhost:5173/login/success?token=$token&user=$user");
        }

        $user = User::query()
            ->where('provider', 'github')
            ->where('provider_id', $githubUser->id)
            ->first();

        if (!$user) {
            return response()->json([
                'errors' => [
                    'credentials' => ['Informations invalides']
                ]
            ], 401);
        }

        $token = $user->createToken('github-token')->plainTextToken;
        $user = $user->load("personne");

        // Encodage JSON pour ne pas casser l'URL (important)
        $encodedUser = urlencode(json_encode($user));

        return redirect("http://localhost:5173/login/success?token=$token&user=$user");
    }

    public function loginWithFacebook(Request $request)
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Impossible de se connecter via Facebook'], 401);
        }

        if (!$facebookUser) {
            return response()->json([
                'errors' => [
                    'token' => ['Token invalide']
                ]
            ], 401);
        }

        $user = User::query()
            ->where('provider', 'facebook')
            ->where('provider_id', $facebookUser->id)
            ->first();

        if (!$user) {
            return response()->json([
                'errors' => [
                    'credentials' => ['Informations invalides']
                ]
            ], 401);
        }

        $token = $user->createToken('facebook-token')->plainTextToken;
        $user = $user->load("personne");

        return redirect("http://localhost:5173/login/success?token=$token&user=" . $user);
    }

    public function loginWithProviderForMobile(Request $request)
    {
        if ($request->userId) {
            $user = User::query()->findOrFail($request->userId);
            $user->provider = $request->provider;
            $user->provider_id = $request->provider_id;
            $user->update();
        } else {
            $user = User::query()
                ->where('provider', $request->provider)
                ->where('provider_id', $request->provider_id)
                ->first();

            if (!$user) {
                return response()->json([
                    'errors' => [
                        'credentials' => ['Informations invalides']
                    ]
                ], 401);
            }
        }

        $token = $user->createToken('mobile-provider')->plainTextToken;

        if ($user->personne->role === 'enseignant') {
            $user->load(['personne', 'personne.enseignant', 'personne.enseignant.matieres', 'personne.enseignant.matieres.niveau', 'personne.enseignant.matieres.niveau.filiere']);
        } elseif ($user->personne->role === 'etudiant' || $user->personne->role === 'delegue') {
            $user->load(['personne', 'personne.etudiant' => function ($query) {
                $query->with('filiere', 'niveau');
            }]);
        } else {
            $user->load('personne');
        }

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 200);
    }
}
