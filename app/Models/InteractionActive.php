<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InteractionActive extends Model
{
    protected $table = 'interactions_active';
    /** @use HasFactory<\Database\Factories\InteractionActiveFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /** Transporter types */
    const T_SUBSTRATE = 1;
    const T_NON_SUBSTRATE = 2;
    const T_INHIBITOR = 3;
    const T_NON_INHIBITOR = 4;
    const T_NA = 5;
    const T_INTERACTS = 6;
    const T_SUBST_INHIB = 7;
    const T_SUBST_NONINHIB = 8;
    const T_NONSUBST_INHIB = 9;
    const T_NONSUBST_NONINHIB = 10;
    const T_ACTIVATOR = 11;
    const T_AGONIST = 12;
    const T_ANTAGONIST = 13;

    private static $enumTypes = array
    (
        self::T_INHIBITOR => 'Inhibitor',
        self::T_SUBSTRATE => 'Substrate',
        self::T_NON_SUBSTRATE => 'Non-substrate',
        self::T_NON_INHIBITOR => 'Non-inhibitor',
        self::T_NA => 'N/A',
        self::T_INTERACTS => 'Interacts',
        self::T_SUBST_INHIB => 'Substrate + inhibitor',
        self::T_SUBST_NONINHIB => 'Substrate + Noninhibitor',
        self::T_NONSUBST_INHIB => 'Nonsubstate + inhibitor',
        self::T_NONSUBST_NONINHIB => 'Nonsubtrate + noninhibitor',
        self::T_ACTIVATOR => 'Activator',
        self::T_AGONIST => 'Agonist',
        self::T_ANTAGONIST => 'Antagonist'
    );

    public static function enumType(?int $type = null) : string|array|null
    {
        if($type)
            return isset(self::$enumTypes[$type]) ? self::$enumTypes[$type] : null;
        return self::$enumTypes;
    }

    /**
     * Returns assigned dataset
     */
    public function dataset() : BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    // /**
    //  * Returns assigned structure ion
    //  */
    // public function ion() : BelongsTo
    // {
    //     return $this->belongsTo(Structure::class);
    // }

    /**
     * Returns assigned substance
     */
    public function structure() : BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    /**
     * Returns assigned protein
     */
    public function protein() : BelongsTo
    {
        return $this->belongsTo(Protein::class);
    }

    public function publication() : BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function isRestoreable() {
        if(!$this?->id)
        {
            return false;
        }

        return $this->structure && $this->dataset && $this->publication && $this->protein;
    }
}
