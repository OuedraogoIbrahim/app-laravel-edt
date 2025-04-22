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

        if (Auth::user()->personne->role == 'admin') {
            return response()->json(Cours::query()->with(['matiere', 'salle', 'filiere', 'niveau'])->get());
        }
        $perPage = $request->query('per_page', 5);
        $courses = Cours::query()
            ->with(['matiere', 'salle', 'filiere', 'niveau'])

            //Pour recuperer les cours a venir
            ->when($request->has('start_date'), function ($query) use ($request) {
                $query->where('date', '>', $request->start_date);
            })

            //Pour recuperer les cours d'aujourd'hui
            ->when($request->isToday, function ($query) {
                $query->whereDate('date', today());
            })
            ->paginate($perPage);
        return response()->json($courses);
        //
    }

    public function weekCourses(Request $request)
    {
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $courses = Cours::query()->whereBetween('date', [$startDate, $endDate])->get();

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
        $courses = Cours::query()
            ->with(['matiere', 'salle', 'filiere', 'niveau'])
            ->where('statut', 'en attente')
            ->whereDate('date', '<', today())
            ->get();

        return response()->json($courses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
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

            $course = new Cours();
            $course->date = $valid['start'];
            $course->heure_debut = $valid['heure_debut'];
            $course->heure_fin = $valid['heure_fin'];
            $course->filiere_id = $valid['filiere'];

            $filiere = Filiere::query()->find($valid['filiere']);
            $niveau = $filiere->niveaux()->where('nom', $valid['niveau'])->first();

            $course->niveau_id = $niveau->id;
            $course->salle_id = Salle::query()->where('nom', $valid['salle'])->first()->id;

            $matiere = $niveau->matieres()->where('nom', $valid['matiere'])->first();
            $course->matiere_id = $matiere->id;
            $course->type = $valid['type'];

            $course->save();

            return response()->json($course, 201);
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

            $course = Cours::findOrFail($id);

            $course->date = $valid['start'];
            $course->heure_debut = $valid['heure_debut'];
            $course->heure_fin = $valid['heure_fin'];
            $course->filiere_id = $valid['filiere'];

            $filiere = Filiere::find($valid['filiere']);
            $niveau = $filiere->niveaux()->where('nom', $valid['niveau'])->firstOrFail();
            $course->niveau_id = $niveau->id;

            $salle = Salle::where('nom', $valid['salle'])->firstOrFail();
            $course->salle_id = $salle->id;

            $matiere = $niveau->matieres()->where('nom', $valid['matiere'])->firstOrFail();
            $course->matiere_id = $matiere->id;

            $course->type = $valid['type'];

            $course->save();

            return response()->json($course, 200);
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
