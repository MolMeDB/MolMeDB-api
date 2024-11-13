<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membrane extends Model
{
    /** @use HasFactory<\Database\Factories\MembraneFactory> */
    use HasFactory;

    /**
     * Types
     */
    const TYPE_PUBCHEM_LOGP = 1;

    private static $valid_types = array
    (
        self::TYPE_PUBCHEM_LOGP  
    );

    private static $enum_types = array
    (
        self::TYPE_PUBCHEM_LOGP => 'Pubchem related'
    );

    /**
     * Returns all valid types
     */
    public static function types()
    {
        return self::$enum_types;
    }


    /**
     * References link
     */
    public function publications() : BelongsToMany
    {
        return $this->belongsToMany(Publication::class);
    }

    /**
     * Returns all assigned passive interactions
     */
    public function passiveInteractions() : HasMany
    {
        return $this->hasMany(InteractionPassive::class);
    }
}
