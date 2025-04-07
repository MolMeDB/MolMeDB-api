<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProteinIdentifier extends Model
{
    /** @use HasFactory<\Database\Factories\SubstanceIdentifierFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [];

    /** IDENTIFIERS CONSTANTS */
    const TYPE_NAME = 1;

    /** ACTIVE STATES */
    const INACTIVE = 0;
    const ACTIVE = 1;

    /** STATES */
    const STATE_NEW = 1;
    const STATE_VALIDATED = 2;
    const STATE_INVALID = 3;

    private static $enum_states = array
    (
        self::STATE_NEW => 'New',
        self::STATE_VALIDATED => 'Validated',
        self::STATE_INVALID => 'Invalid',  
    );

    /**
     * Enum types of identifiers
     */
    private static $enum_types = array
    (
        self::TYPE_NAME => 'Name',
    );

    /**
     * Enum active states
     */
    private static $enum_active_states = array
    (
        self::INACTIVE => "inactive",
        self::ACTIVE => 'active'
    );

    public static function enumType($type) : string 
    {
        return isset(self::$enum_types[$type]) ? self::$enum_types[$type] : 'N/A';
    }

    /**
     * Returns all available identifier types
     */
    public static function types() : array
    {
        return self::$enum_types;
    }

    /**
     * Returns all available states
     */
    public static function states() : array
    {
        return self::$enum_states;
    }

    /**
     * Returns assigned substance
     */
    public function protein() : BelongsTo
    {
        return $this->belongsTo(Protein::class);
    }

    public function source() : MorphTo
    {
        return $this->morphTo();
    }
}
