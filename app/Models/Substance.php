<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Substance extends Model
{
    /** @use HasFactory<\Database\Factories\SubstanceFactory> */
    use HasFactory;

    /**
     * Returns all assigned passive interactions
     */
    public function passiveInteractions() : HasMany
    {
        return $this->hasMany(InteractionPassive::class);
    }

    /**
     * Returns all assigned identifiers
     */
    public function identifiers() : HasMany {
        return $this->hasMany(SubstanceIdentifier::class);
    }

    /**
     * Returns assigned primary structure
     */
    public function structure() : HasMany {
        return $this->hasMany(Structure::class);
    }
}
