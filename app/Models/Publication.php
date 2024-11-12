<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publication extends Model
{
    /** @use HasFactory<\Database\Factories\PublicationFactory> */
    use HasFactory;

    /**
     * TYPES
     */
    const TYPE_PUBCHEM = 1;
    const TYPE_CHEMBL = 2;
    const TYPE_COSMO = 3;

    private static $enum_types = [
        self::TYPE_PUBCHEM => 'Pubchem',
        self::TYPE_CHEMBL => 'ChEMBL',
        self::TYPE_COSMO => 'COSMO'
    ];

    public static function types() : array
    {
        return self::$enum_types;
    }

    /**
     * Returns all assigned membranes
     */
    public function membranes(): BelongsToMany
    {
        return $this->belongsToMany(Membrane::class);
    }

    /**
     * Returns all assigned methods
     */
    public function methods(): BelongsToMany
    {
        return $this->belongsToMany(Method::class);
    }
}
