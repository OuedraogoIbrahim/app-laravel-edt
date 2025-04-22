<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matiere extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'description', 'periode', 'heures_utilisees', 'nombre_heures', 'niveau_id'];


    // public function filiere(): BelongsToMany
    // {
    //     return $this->belongsToMany(Filiere::class);
    // }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(Enseignant::class);
    }


    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class);
    }
}
