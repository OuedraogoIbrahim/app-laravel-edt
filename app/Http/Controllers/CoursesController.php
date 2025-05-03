<?php

namespace App\Http\Controllers;

use App\Models\Cours;
use App\Models\Filiere;
use App\Models\Salle;
use App\Models\User;
use App\Notifications\CourseSchedule;
use App\Notifications\ExpoNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->personne->role == 'admin') {
            return response()->json(
                Cours::query()
                    ->with(['matiere', 'salle', 'filiere', 'niveau'])
                    ->get()
            );
        }

        $perPage = $request->query('per_page', 5);

        $query = Cours::query()
            ->with(['matiere', 'salle', 'filiere', 'niveau']);

        // Pour récupérer les cours à venir
        $query->when($request->has('start_date'), function ($q) use ($request) {
            $q->where('date', '>', $request->start_date);
        });

        // Pour récupérer les cours d'aujourd'hui
        $query->when($request->isToday, function ($q) {
            $q->whereDate('date', today());
        });

        // Application du filtre selon le rôle
        if ($user->personne->role == 'enseignant') {
            $query->whereHas('matiere.enseignants', function ($q) use ($user) {
                $q->where('enseignants.id', $user->personne->enseignant->id);
            });
        } elseif ($user->personne->role == 'etudiant' || $user->personne->role == 'delegue' || $user->personne->role == 'parent') {
            $query->where('niveau_id', $user->personne->etudiant->niveau_id);
        }

        $courses = $query->paginate($perPage);

        return response()->json($courses);
    }


    // public function weekCourses(Request $request)
    // {
    //     $startDate = $request->startDate;
    //     $endDate = $request->endDate;

    //     $courses = Cours::query()
    //         ->with(['matiere', 'salle', 'filiere', 'niveau'])
    //         ->when(Auth::user()->personne->role == 'etudiant', function ($query) use ($request) {
    //             $query->where('niveau_id', Auth::user()->personne->etudiant->niveau_id);
    //         })
    //         ->whereBetween('date', [$startDate, $endDate])
    //         ->get();

    //     return response()->json($courses);
    // }
    public function weekCourses(Request $request)
    {
        $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'niveau_id' => 'nullable|integer|exists:niveaux,id',
        ]);

        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $niveauId = $request->niveau_id;

        $courses = Cours::query()
            ->with(['matiere', 'salle', 'filiere', 'niveau'])
            ->when(Auth::user()->personne->role == 'etudiant' || Auth::user()->personne->role == 'delegue' || Auth::user()->personne->role == 'parent', function ($query) use ($request) {
                $query->where('niveau_id', Auth::user()->personne->etudiant->niveau_id);
            })
            ->when($niveauId && Auth::user()->personne->role == 'enseignant', function ($query) use ($niveauId) {
                $query->where('niveau_id', $niveauId);
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return response()->json($courses);
    }

    public function cancelCourse(Cours $course)
    {
        $course->statut = 'annuler';
        $course->update();

        $users = User::all();

        $title = "Annulation de Cours";
        $message = "Un cours a été annulé.";

        Notification::send($users, new CourseSchedule($course, $title, $message));
        Notification::send($users, new ExpoNotification('Annulation de cours', 'Le cours de ' . $course->matiere->nom . "initialement prévu le " . $course->date . "a été annulé"));
        return response()->json('cours annulé avec succès', 201);
    }

    public function acceptCourse(Cours $course)
    {
        $course->statut = 'en attente';
        $course->update();

        $users = User::all();

        $title = "Reprogrammation de Cours";
        $message = "Un cours a été reprogrammé.";

        Notification::send($users, new CourseSchedule($course, $title, $message));
        Notification::send($users, new ExpoNotification('Reprogrammation de Cours', 'Le cours de ' . $course->matiere->nom .  " a été reprogrammé le " . $course->date));

        return response()->json('cours reprogrammé avec succès', 201);
    }

    public function pendingValidation(Request $request)
    {
        $user = Auth::user();

        $query = Cours::query()
            ->with(['matiere', 'salle', 'filiere', 'niveau'])
            ->where('statut', 'en attente')
            ->whereDate('date', '<', today());

        if ($user->personne->role == 'enseignant') {
            $query->whereHas('matiere.enseignants', function ($q) use ($user) {
                $q->where('enseignants.id', $user->personne->enseignant->id);
            });
        } elseif ($user->personne->role == 'etudiant' || $user->personne->role == 'delegue') {
            $query->where('niveau_id', $user->personne->etudiant->niveau_id);
        }

        $courses = $query->get();

        return response()->json($courses);
    }

    public function courseCompleted(Cours $course)
    {
        $course->statut = 'terminer';
        $course->commentaire = 'Aucun commentaire';
        $course->valide_par = 'delegué';
        $course->date_validation = now();
        $course->update();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // 1. Validation
            $valid = $request->validate([
                'start' => 'required|date',
                'heure_debut' => 'required|string',
                'heure_fin' => 'required|string',
                'filiere' => 'required|string|exists:filieres,id',
                'niveau' => 'required|string|exists:niveaux,nom',
                'salle' => 'required|string',
                'matiere' => 'required|string|exists:matieres,nom',
                'type' => 'required|in:cours,devoir,autre'
            ]);

            // 2. Chargement intelligent des données
            $filiere = Filiere::findOrFail($valid['filiere']);
            $niveau = $filiere->niveaux()->where('nom', $valid['niveau'])->firstOrFail();
            $matiere = $niveau->matieres()->where('nom', $valid['matiere'])->firstOrFail();
            $salle = Salle::where('nom', $valid['salle'])->firstOrFail();

            $enseignant = $matiere->enseignants->first(); // récupération du 1er enseignant lié

            if (!$enseignant) {
                return response()->json([
                    'errors' => 'Aucun enseignant n\'est associé à cette matière.'
                ], 422);
            }

            $debut = $valid['heure_debut'];
            $fin = $valid['heure_fin'];

            // 3. Vérification de conflit d'enseignant
            $conflitProfesseur = Cours::where('date', $valid['start'])
                ->whereHas('matiere.enseignants', function ($query) use ($enseignant) {
                    $query->where('enseignants.id', $enseignant->id);
                })
                ->where(function ($query) use ($debut, $fin) {
                    $query->where('heure_debut', '<', $fin)
                        ->where('heure_fin', '>', $debut);
                })
                ->exists();

            if ($conflitProfesseur) {
                return response()->json([
                    'errors' => 'Conflit d\'horaire : Le professeur a déjà un cours programmé durant cette période.'
                ], 409);
            }

            // 4. Vérification de conflit de salle
            $conflitSalle = Cours::where('date', $valid['start'])
                ->where('salle_id', $salle->id)
                ->where(function ($query) use ($debut, $fin) {
                    $query->where('heure_debut', '<', $fin)
                        ->where('heure_fin', '>', $debut);
                })
                ->exists();

            if ($conflitSalle) {
                return response()->json([
                    'errors' => 'Conflit de salle : La salle est déjà occupée durant cette période.'
                ], 409);
            }

            // 5. Création du Cours
            $cours = Cours::create([
                'date' => $valid['start'],
                'heure_debut' => $valid['heure_debut'],
                'heure_fin' => $valid['heure_fin'],
                'filiere_id' => $filiere->id,
                'niveau_id' => $niveau->id,
                'salle_id' => $salle->id,
                'matiere_id' => $matiere->id,
                'type' => $valid['type'],
            ]);

            return response()->json($cours, 201);
        } catch (\Throwable $th) {
            return response()->json(['errors' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // 1. Validation
            $valid = $request->validate([
                'start' => 'required|date',
                'heure_debut' => 'required|string',
                'heure_fin' => 'required|string',
                'filiere' => 'required|string|exists:filieres,id',
                'niveau' => 'required|string|exists:niveaux,nom',
                'salle' => 'required|string',
                'matiere' => 'required|string|exists:matieres,nom',
                'type' => 'required|in:cours,devoir,autre'
            ]);

            $cours = Cours::findOrFail($id);

            $filiere = Filiere::findOrFail($valid['filiere']);
            $niveau = $filiere->niveaux()->where('nom', $valid['niveau'])->firstOrFail();
            $matiere = $niveau->matieres()->where('nom', $valid['matiere'])->firstOrFail();
            $salle = Salle::where('nom', $valid['salle'])->firstOrFail();

            $enseignant = $matiere->enseignants->first(); // récupération du 1er enseignant lié

            if (!$enseignant) {
                return response()->json([
                    'errors' => 'Aucun enseignant n\'est associé à cette matière.'
                ], 422);
            }

            $debut = $valid['heure_debut'];
            $fin = $valid['heure_fin'];

            // 4. Vérification de conflit d'enseignant (exclure ce cours)
            $conflitProfesseur = Cours::where('date', $valid['start'])
                ->where('id', '!=', $cours->id)
                ->whereHas('matiere.enseignants', function ($query) use ($enseignant) {
                    $query->where('enseignants.id', $enseignant->id);
                })
                ->where(function ($query) use ($debut, $fin) {
                    $query->where('heure_debut', '<', $fin)
                        ->where('heure_fin', '>', $debut);
                })
                ->exists();

            if ($conflitProfesseur) {
                return response()->json([
                    'errors' => 'Conflit d\'horaire : Le professeur a déjà un cours programmé durant cette période.'
                ], 409);
            }

            // 5. Vérification de conflit de salle (exclure ce cours)
            $conflitSalle = Cours::where('date', $valid['start'])
                ->where('id', '!=', $cours->id)
                ->where('salle_id', $salle->id)
                ->where(function ($query) use ($debut, $fin) {
                    $query->where('heure_debut', '<', $fin)
                        ->where('heure_fin', '>', $debut);
                })
                ->exists();

            if ($conflitSalle) {
                return response()->json([
                    'errors' => 'Conflit de salle : La salle est déjà occupée durant cette période.'
                ], 409);
            }

            // 6. Mise à jour du cours
            $cours->update([
                'date' => $valid['start'],
                'heure_debut' => $valid['heure_debut'],
                'heure_fin' => $valid['heure_fin'],
                'filiere_id' => $filiere->id,
                'niveau_id' => $niveau->id,
                'salle_id' => $salle->id,
                'matiere_id' => $matiere->id,
                'type' => $valid['type'],
            ]);

            return response()->json($cours, 200);
        } catch (\Throwable $th) {
            return response()->json(['errors' => $th->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $course = Cours::findOrFail($id);
        $course->delete();
        return response()->json(null, 204);
    }
}
