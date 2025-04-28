<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Enseignant extends Model
{
    use HasFactory;

    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }

    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(Matiere::class);
    }

    public function niveaux(): BelongsToMany
    {
        return $this->belongsToMany(Niveau::class);
    }
}
