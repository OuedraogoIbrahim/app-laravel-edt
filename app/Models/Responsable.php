<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Responsable extends Model
{
    use HasFactory;

    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }
}
