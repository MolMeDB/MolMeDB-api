<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Method extends Model
{
    /** @use HasFactory<\Database\Factories\MethodFactory> */
    use HasFactory;

    /**
     * Types
     */
    const TYPE_PUBCHEM_LOGP = 1;
    const TYPE_CHEMBL_LOGP = 2;
    const TYPE_COSMO18 = 3;

    private static $valid_types = array
    (
        self::TYPE_PUBCHEM_LOGP,
        self::TYPE_CHEMBL_LOGP,
        self::TYPE_COSMO18
    );

    private static $enum_types = array
    (
        self::TYPE_PUBCHEM_LOGP => 'Pubchem related',
        self::TYPE_CHEMBL_LOGP  => 'ChEMBL related',
        self::TYPE_COSMO18 => 'COSMO18 related'
    );

    /**
     * Returns all valid types
     */
    public static function types() : array
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
