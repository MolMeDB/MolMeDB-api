<?php

namespace App\Models;

use Modules\PredictionWorkers\Models\PredictionStructure;
use Database\Factories\SubstanceFactory;
use EloquentFilter\Filterable;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Structure extends BaseModel
{
    /** @use HasFactory<SubstanceFactory> */
    use HasFactory, SoftDeletes;
    use Filterable;

    protected $with = ['identifiers'];

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
        return $this->hasMany(Identifier::class)
            ->orderBy('id', 'asc');
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

    public function fluorescentProperties() : HasMany {
        return $this->hasMany(FluorescentProperty::class);
    }

    public function predictionStructure() : HasOne {
        return $this->hasOne(PredictionStructure::class, 'remote_id');
    }

    public function chargedChildren() : HasMany {
        return $this->hasMany(Structure::class, 'parent_id');
    }

    public function links() : HasMany {
        return $this->hasMany(StructureLink::class);
    }

    public function isForceDeletable() : bool {
        return $this->interactionsPassive()->withTrashed()->count() == 0
            && $this->interactionsActive()->withTrashed()->count() == 0
            && $this->fluorescentProperties()->withTrashed()->count() == 0
            && $this->children()->withTrashed()->count() == 0;
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

    public function getNameAttribute()
    {
        return $this->identifiers
            ->where('type', Identifier::TYPE_NAME)
            ->where('state', '!=', Identifier::STATE_INVALID)            
            ->sortByDesc('state')
            ->first()?->value;
    }

    public function getPdbAttribute()
    {
        return $this->identifiers
            ->where('type', Identifier::TYPE_PDB)
            ->where('state', '!=', Identifier::STATE_INVALID)            
            ->sortByDesc('state')
            ->first()?->value;
    }

    public function getPubchemAttribute()
    {
        return $this->identifiers
            ->where('type', Identifier::TYPE_PUBCHEM)
            ->where('state', '!=', Identifier::STATE_INVALID)            
            ->sortByDesc('state')
            ->first()?->value;
    }

    public function getDrugbankAttribute()
    {
        return $this->identifiers
            ->where('type', Identifier::TYPE_DRUGBANK)
            ->where('state', '!=', Identifier::STATE_INVALID)            
            ->sortByDesc('state')
            ->first()?->value;
    }

    public function getChemblAttribute()
    {
        return $this->identifiers
            ->where('type', Identifier::TYPE_CHEMBL)
            ->where('state', '!=', Identifier::STATE_INVALID)            
            ->sortByDesc('state')
            ->first()?->value;
    }

    public function getChebiAttribute()
    {
        return $this->identifiers
            ->where('type', Identifier::TYPE_CHEBI)
            ->where('state', '!=', Identifier::STATE_INVALID)            
            ->sortByDesc('state')
            ->first()?->value;
    }

    public static function join_structures(self $structure_1, self $structure_2)
    {
        // Reassign all related data from structure_2 to structure_1
        foreach($structure_2->identifiers as $identifier)
        {
            // Check if exists
            if($structure_1->identifiers()
                ->where('type', $identifier->type)
                ->where('value', $identifier->value)
                ->where('source_type', $identifier->source_type)
                ->where('source_id', $identifier->source_id)
                ->exists())
                continue;

            $identifier->structure_id = $structure_1->id;
            $identifier->save();
        }

        foreach($structure_2->interactionsActive as $interaction)
        {
            $interaction->structure_id = $structure_1->id;
            $interaction->save();
        }

        foreach($structure_2->interactionsPassive as $interaction)
        {
            $interaction->structure_id = $structure_1->id;
            $interaction->save();
        }

        foreach($structure_2->fluorescentProperties as $property)
        {
            $property->structure_id = $structure_1->id;
            $property->save();
        }

        foreach($structure_2->links as $link)
        {
            $link->structure_id = $structure_1->id;
            $link->save();
        }

        // Create new structure_link
        $structure_1->links()->create([
            'identifier' => $structure_2->identifier
        ]);

        // Delete structure_2
        $structure_2->delete();
    }
}
