<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publication extends Model
{
    /** @use HasFactory<\Database\Factories\PublicationFactory> */
    use HasFactory;

    protected $guarded = [];

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
     * Returns enum type
     */
    public static function enumType($type) : string 
    {
        if(isset(self::$enum_types[$type]))
            return self::$enum_types[$type];
        return null;
    }

    /**
     * Returns all assigned membranes, which are described in the current publication
     */
    public function membranes(): BelongsToMany
    {
        return $this->belongsToMany(Membrane::class);
    }

    /**
     * Returns all assigned methods, which are described in the current publication
     */
    public function methods(): BelongsToMany
    {
        return $this->belongsToMany(Method::class);
    }

    /**
     * Returns all assigned passive interactions
     */
    public function passiveInteractions() : HasMany
    {
        return $this->hasMany(InteractionPassive::class);
    }

    /**
     * Returns assigned user - record author
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
