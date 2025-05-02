<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Niveau;
use App\Models\Matiere;
use App\Models\EdtProvisoire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EdtProvisoireController extends Controller
{

    public function index()
    {
        $edtProvisoire = EdtProvisoire::select([
            'date_creation',
            'niveau_id',
            DB::raw('MAX(date) as date'),
            DB::raw('MAX(heure_debut) as heure_debut'),
            DB::raw('MAX(heure_fin) as heure_fin'),
            DB::raw('MAX(statut) as statut'),
            DB::raw('MAX(activite) as activite'),
            DB::raw('MAX(type) as type'),
            DB::raw('MAX(salle_id) as salle_id'),
            DB::raw('MAX(filiere_id) as filiere_id'),
            DB::raw('MAX(niveau_id) as niveau_id'),
            DB::raw('MAX(matiere_id) as matiere_id'),
            DB::raw('COUNT(*) as count')
        ])
            ->groupBy('date_creation', 'niveau_id')
            ->get();

        return response()->json($edtProvisoire, 201);
    }



    public function create(Request $request)
    {
        try {
            // Validation des données
            $validatedData = $request->validate([
                'semestre' => 'required|in:semestre 1,semestre 2',
                'filiere' => 'required|string|exists:filieres,id',
                'niveau' => 'required|string|exists:niveaux,nom',
                'salle' => 'required|string',
                'matiere' => 'required|array',
                'matiere.*' => 'required|exists:matieres,id',
            ]);

            // Récupérer l'ID du niveau
            $niveauId = $this->getNiveauId($validatedData['filiere'], $validatedData['niveau']);

            // Déterminer les dates du semestre
            $startDate = $validatedData['semestre'] === 'semestre 1' ? '2024-10-01' : '2025-03-01';
            $endDate = $validatedData['semestre'] === 'semestre 1' ? '2025-02-28' : '2025-07-31';

            // Récupérer les matières avec leurs heures totales
            $matiereIds = $validatedData['matiere'];
            $matieres = Matiere::whereIn('id', $matiereIds)->pluck('nombre_heures', 'id')->toArray();

            // Initialiser les heures restantes pour chaque matière
            $heuresRestantes = $matieres;

            // Vérifier si des heures sont disponibles
            if (array_sum($heuresRestantes) === 0) {
                return response()->json(['errors' => 'Aucune heure disponible pour les matières sélectionnées'], 400);
            }

            // Générer les créneaux horaires
            $currentDate = Carbon::parse($startDate);

            while ($currentDate <= Carbon::parse($endDate) && array_sum($heuresRestantes) > 0) {
                for ($i = 0; $i < 5; $i++) { // Lundi à vendredi
                    $dayOfWeek = $currentDate->format('l');

                    // Sélectionner une matière en fonction des heures restantes
                    $matiereId = $this->selectMatiere($heuresRestantes);
                    if ($matiereId === null) {
                        break 2; // Aucune matière disponible, arrêter
                    }

                    if ($dayOfWeek === 'Saturday') {
                        // Samedi : cours le matin uniquement
                        $this->createScheduleEntry(
                            $currentDate,
                            '08:00:00',
                            '12:00:00',
                            $validatedData,
                            $matiereId,
                            $niveauId
                        );
                        $heuresRestantes[$matiereId] -= 4; // Déduire 4 heures
                    } elseif (in_array($dayOfWeek, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])) {
                        // Lundi à vendredi : cours le matin et le soir
                        $this->createScheduleEntry(
                            $currentDate,
                            '08:00:00',
                            '12:00:00',
                            $validatedData,
                            $matiereId,
                            $niveauId
                        );
                        $heuresRestantes[$matiereId] -= 4; // Déduire 4 heures

                        Log::info("Ajout reussie");

                        // Vérifier si des heures restent pour une nouvelle matière
                        $matiereId = $this->selectMatiere($heuresRestantes);
                        if ($matiereId === null) {
                            break 2; // Aucune matière disponible
                        }

                        $this->createScheduleEntry(
                            $currentDate,
                            '14:00:00',
                            '18:00:00',
                            $validatedData,
                            $matiereId,
                            $niveauId
                        );
                        $heuresRestantes[$matiereId] -= 4; // Déduire 4 heures
                    }

                    $currentDate->addDay();
                }

                // Passer à la semaine suivante (sauter samedi et dimanche)
                $currentDate->addDays(2);
            }

            return response()->json(['message' => 'EDT provisoire créé avec succès'], 201);
        } catch (\Throwable $th) {
            return response()->json(['errors' => $th->getMessage()], 500);
        }
    }

    private function getNiveauId($filiereId, $niveauNom)
    {
        return Niveau::where('nom', $niveauNom)
            ->whereHas('filiere', function ($query) use ($filiereId) {
                $query->where('id', $filiereId);
            })->value('id');
    }

    private function createScheduleEntry($date, $heureDebut, $heureFin, $validatedData, $matiereId, $niveauId)
    {
        EdtProvisoire::create([
            'date' => $date->format('Y-m-d'),
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'statut' => 'en attente',
            'type' => 'cours',
            'salle_id' => $validatedData['salle'],
            'filiere_id' => $validatedData['filiere'],
            'niveau_id' => $niveauId,
            'matiere_id' => $matiereId,
        ]);
    }

    private function selectMatiere($heuresRestantes)
    {
        // Filtrer les matières avec des heures restantes
        $matieresDisponibles = array_filter($heuresRestantes, fn($heures) => $heures > 0);
        if (empty($matieresDisponibles)) {
            return null;
        }

        // Calculer le total des heures restantes
        $totalHeures = array_sum($matieresDisponibles);

        // Calculer les poids (probabilités) pour chaque matière
        $poids = array_map(fn($heures) => $heures / $totalHeures, $matieresDisponibles);

        // Générer un nombre aléatoire entre 0 et 1
        $rand = mt_rand() / mt_getrandmax();
        $cumul = 0;

        // Choisir une matière en fonction des poids
        foreach ($matieresDisponibles as $matiereId => $heures) {
            $cumul += $poids[$matiereId];
            if ($rand <= $cumul) {
                return $matiereId;
            }
        }

        // En cas d'erreur, retourner la dernière matière
        return array_key_last($matieresDisponibles);
    }
}
