<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EdtProvisoire extends Model
{
    /** @use HasFactory<\Database\Factories\EdtProvisoireFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'heure_debut',
        'heure_fin',
        'statut',
        'type',
        'salle_id',
        'filiere_id',
        'niveau_id',
        'matiere_id'
    ];
}
