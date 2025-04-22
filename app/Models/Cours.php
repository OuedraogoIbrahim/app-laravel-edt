<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cours extends Model
{
    use HasFactory;

    protected $fillable = [];

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }
}
