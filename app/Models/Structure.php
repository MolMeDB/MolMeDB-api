<?php

namespace App\Models;

use EloquentFilter\Filterable;
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
    use Filterable;

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

    public function nameIdentifier() : HasMany {
        return $this->identifiers()
            ->where('type', Identifier::TYPE_NAME)
            ->whereNot('state', Identifier::STATE_INVALID)            
            ->orderBy('state', 'desc')
            ->orderBy('id', 'asc')
            ->limit(1);
    }

    public function changeMainIdentifier($newIdentifier)
    {
        if(!$newIdentifier)
            return;

        if(StructureLink::where('identifier', $newIdentifier)->exists()
            || Structure::where('identifier', $newIdentifier)->exists())
        {
            throw new Exception('Identifier ' . $newIdentifier . ' is already in use.');
        }

        if(!$this->identifier)
        {
            $this->identifier = $newIdentifier;
            $this->save();
            return;
        }

        // Remove, if exists invalid record
        StructureLink::where('identifier', $this->identifier)?->delete();

        $this->links()->create([
            'identifier' => $this->identifier
        ]);

        $this->identifier = $newIdentifier;
        $this->save();
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

    public function links() : HasMany {
        return $this->hasMany(StructureLink::class);
    }

    public function isRestoreable() : bool {
        if(!$this?->id)
            return false;
        return $this->parent && true;
    }

    public function setParent(Structure $parent)
    {
        foreach($this->children()->get() as $child)
        {
            $child->parent_id = $parent->id;
            $child->save();
        }

        $this->parent_id = $parent->id;
        $this->save();
    }
}
