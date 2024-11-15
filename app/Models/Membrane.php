<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membrane extends Model
{
    /** @use HasFactory<\Database\Factories\MembraneFactory> */
    use HasFactory;

    protected $guarded = [];

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
        self::TYPE_PUBCHEM_LOGP => 'PubChem'
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
            ->where('model', Membrane::class);
    }

    /**
     * Returns assigned category
     */
    public function category() : BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
