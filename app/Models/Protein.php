<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Protein extends Model
{
    /** @use HasFactory<\Database\Factories\ProteinFactory> */
    use HasFactory;

    /**
     * Returns all assigned active interactions
     */
    public function interactions_active() : HasMany
    {
        return $this->hasMany(InteractionActive::class);
    }
}
