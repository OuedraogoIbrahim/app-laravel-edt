<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admin extends Model
{
    /** @use HasFactory<\Database\Factories\AdminFactory> */
    use HasFactory;

    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }
}
