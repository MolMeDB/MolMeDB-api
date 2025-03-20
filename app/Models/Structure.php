<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Structure extends Model
{
    /** @use HasFactory<\Database\Factories\SubstanceFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Structure $structure) {
            // Delete related data
            $structure->identifiers()->delete();
            $structure->interactionsActive()->delete();
            $structure->interactionsPassive()->delete();
            foreach($structure->chargedChildren as $ion)
                $ion->delete();
        });

        static::restoring(function (Structure $structure) {
            // Restore related data
            $structure->identifiers()->restore();
            $structure->interactionsActive()->restore();
            $structure->interactionsPassive()->restore();
            foreach($structure->chargedChildren()->withTrashed()->get() as $ion)
                $ion->restore();
        });

        static::forceDeleting(function (Structure $structure) {
            $structure->identifiers()->forceDelete();
            $structure->interactionsActive()->forceDelete();
            $structure->interactionsPassive()->forceDelete();
            foreach($structure->chargedChildren()->withTrashed()->get() as $ion)
                $ion->forceDelete();
        });
    }

    public function identifiers() : HasMany {
        return $this->hasMany(Identifier::class);
    }

    public function nameIdentifier()  {
        return $this->identifiers()
            ->where('type', Identifier::TYPE_NAME)
            ->limit(1);
    }

    public function parent() : BelongsTo {
        return $this->belongsTo(Structure::class, 'parent_id');
    }

    public function children() : HasMany {
        return $this->hasMany(Structure::class, 'parent_id');
    }

    public function interactionsPassive() : HasMany {
        return $this->hasMany(InteractionPassive::class);
    }

    public function interactionsActive() : HasMany {
        return $this->hasMany(InteractionActive::class);
    }

    public function chargedChildren() : HasMany {
        return $this->hasMany(Structure::class, 'parent_id');
    }

    public function isRestoreable() : bool {
        if(!$this?->id)
            return false;
        return $this->parent && true;
    }

    public function generateIdentifier()
    {
        $owner = $this->parent()->first() ?? $this;

        if(!$owner->identifier)
        {// Parent must have identifier from upload
            throw new Exception('Structure has no identifier');
        }

        $last_id = $owner->children()->orderby('identifier', 'desc')->first();

        if(!$last_id || !preg_match('/\.\d+$/', $last_id->identifier)) {
            return $owner->identifier . '.1';
        }

        $last_id = intval(explode('.', $last_id->identifier)[1]) + 1;
        return $owner->identifier . '.' . $last_id;
    }
}
