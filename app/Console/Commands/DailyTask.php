<?php

namespace App\Console\Commands;

use App\Models\Cours;
use App\Models\User;
use App\Notifications\CourseReminder;
use App\Notifications\ExpoNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class DailyTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Récupérer les cours qui auront lieu demain
        $coursDemain = Cours::with(['filiere.etudiants.personne.user', 'matiere.enseignants.personne.user'])->whereDate('date', now()->addDay())->get();

        if ($coursDemain->isEmpty()) {
            $this->info('Aucun cours prévu pour demain.');
            return;
        }

        $users = collect();

        foreach ($coursDemain as $cours) {
            // 2. Ajouter les étudiants de la filière
            foreach ($cours->filiere->etudiants as $etudiant) {
                if ($etudiant->personne && $etudiant->personne->user) {
                    $users->push($etudiant->personne->user);

                    // 3. Ajouter les parents (si existants)
                    if ($etudiant->personne->parents) {
                        foreach ($etudiant->personne->parents as $parent) {
                            if ($parent->user) {
                                $users->push($parent->user);
                            }
                        }
                    }
                }
            }

            // 4. Ajouter les professeurs de la matière
            foreach ($cours->matiere->enseignants as $enseignant) {
                if ($enseignant->personne && $enseignant->personne->user) {
                    $users->push($enseignant->personne->user);
                }
            }
        }

        $users = $users->unique('id'); // On évite les doublons

        if ($users->isEmpty()) {
            $this->info('Aucun utilisateur à notifier.');
            return;
        }

        // 5. Envoyer les notifications
        Notification::send($users, new CourseReminder($coursDemain));
        Notification::send($users, new ExpoNotification('Rappel Cours', "Vous avez un cours prévue pour demain."));

        $this->info('Notifications envoyées avec succès.');
    }
}
