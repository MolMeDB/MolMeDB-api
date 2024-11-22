<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Method extends Model
{
    /** @use HasFactory<\Database\Factories\MethodFactory> */
    use HasFactory;

    protected $guarded = [];

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
    public static function types()
    {
        return self::$enum_types;
    }

    /**
     * Returns enum type
     */
    public static function enumType($type)
    {
        if(isset(self::$enum_types[$type]))
            return self::$enum_types[$type];
        return null;
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

    /**
     * Returns assigned user - record author
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns all assigned keywords
     */
    public function keywords() : HasMany
    {
        return $this->hasMany(Keyword::class, 'model_id', 'id')
            ->where('model', Method::class);
    }

    /**
     * Returns assigned category
     */
    public function category() : BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
