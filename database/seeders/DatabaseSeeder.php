<?php

use App\Models\Admin;
use Illuminate\Database\Seeder;
use App\Models\Cours;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Personne;
use App\Models\Salle;
use App\Models\User;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $user = new User();
        $user->email = 'admin@gmail.com';
        $user->password = bcrypt('password');
        $user->save();

        $personne = new Personne();
        $personne->nom = "admin";
        $personne->prenom = 'admin';
        $personne->sexe = 'M';
        $personne->role = 'admin';
        $personne->date_naissance = '2222-12-12';
        $personne->tel = 50505050;
        $personne->user_id = $user->id;

        $personne->save();

        $admin = new Admin();
        $admin->personne_id = $personne->id;
        $admin->save();

        // Créer des salles
        $salles = Salle::factory(5)->create();

        $filieresData = [
            'ABF' => [
                'L1' => ['Comptabilité générale', 'Droit des affaires', 'Introduction à la gestion'],
                'L2' => ['Analyse financière', 'Contrôle de gestion', 'Fiscalité'],
                'L3' => ['Audit', 'Gestion budgétaire', 'Comptabilité approfondie'],
            ],
            'ADB' => [
                'L1' => ['Économie générale', 'Marketing fondamental', 'Mathématiques financières'],
                'L2' => ['Management', 'Gestion des ressources humaines', 'Techniques commerciales'],
                'L3' => ['Stratégie d’entreprise', 'Entrepreneuriat', 'Gestion de projet'],
            ],
            'MIAGE' => [
                'L1' => ['Algorithmique', 'Programmation en C', 'Mathématiques discrètes'],
                'L2' => ['Base de données', 'Systèmes d’exploitation', 'Programmation orientée objet'],
                'L3' => ['Réseaux', 'Génie logiciel', 'Business Intelligence'],
            ],
            'MID' => [
                'L1' => ['Mathématiques I', 'Physique I', 'Informatique générale'],
                'L2' => ['Probabilités', 'Statistiques', 'Analyse numérique'],
                'L3' => ['Big Data', 'Intelligence artificielle', 'Analyse de données'],
            ]
        ];

        foreach ($filieresData as $nomFiliere => $niveaux) {
            $filiere = new Filiere();
            $filiere->nom = $nomFiliere;
            $filiere->description = 'Description de la filière ' . $nomFiliere;
            $filiere->save();

            foreach ($niveaux as $niveauCle => $matieres) {
                $niveau = new Niveau();
                $niveau->nom = $niveauCle . ' ' . $nomFiliere;
                $niveau->description = 'Description du niveau ' . $niveauCle;
                $niveau->filiere_id = $filiere->id;
                $niveau->save();

                $matiereInstances = [];

                foreach ($matieres as $nomMatiere) {
                    $matiere = new Matiere();
                    $matiere->nom = $nomMatiere;
                    $matiere->periode = fake()->randomElement(['semestre 1', 'semestre 2']);
                    $matiere->description = 'Cours de ' . $nomMatiere;
                    $matiere->nombre_heures = fake()->numberBetween(20, 60);
                    $matiere->heures_utilisees = 0;
                    $matiere->niveau_id = $niveau->id;
                    $matiere->save();
                    $matiereInstances[] = $matiere;
                }

                // Pour chaque jour de la semaine, créer un cours lié au niveau et à une matière
                for ($j = 0; $j < 7; $j++) {
                    $cours = new Cours();
                    $cours->date = Carbon::now()->addDays($j)->format('Y-m-d');

                    $plageHoraires = [
                        ['heure_debut' => '08:00', 'heure_fin' => '12:00'],
                        ['heure_debut' => '14:00', 'heure_fin' => '18:00']
                    ];
                    $plage = fake()->randomElement($plageHoraires);
                    $cours->heure_debut = $plage['heure_debut'];
                    $cours->heure_fin = $plage['heure_fin'];

                    $statut = fake()->randomElement(['en attente', 'terminer', 'annuler']);
                    $cours->statut = $statut;
                    $cours->type = fake()->randomElement(['cours', 'devoir', 'autre']);
                    $cours->salle_id = $salles->random()->id;
                    $cours->filiere_id = $filiere->id;
                    $cours->niveau_id = $niveau->id;
                    $cours->matiere_id = collect($matiereInstances)->random()->id;

                    if ($statut === 'terminer') {
                        $cours->commentaire = fake()->sentence();
                        $cours->valide_par = fake()->name();
                        $cours->date_validation = fake()->dateTimeBetween('-1 year', 'now');
                    }

                    $cours->save();
                }
            }
        }
    }
}
