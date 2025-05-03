<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'description'];

    // public function matieres(): BelongsToMany
    // {
    //     return $this->belongsToMany(Matiere::class);
    // }

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class);
    }

    public function etudiant()
    {
        return $this->hasMany(Etudiant::class);
    }

    public function etudiants(): HasMany
    {
        return $this->hasMany(Etudiant::class);
    }

    public function parents(): HasMany
    {
        return $this->hasMany(Parant::class);
    }


    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class);
    }
}
