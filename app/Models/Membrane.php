<?php

namespace App\Models;

use Dflydev\DotAccessData\Data;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membrane extends Model
{
    /** @use HasFactory<\Database\Factories\MembraneFactory> */
    use HasFactory, SoftDeletes;
    use Filterable;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Membrane $membrane) {
            // Delete related data
            foreach($membrane->datasets as $ds)
                $ds->delete();
            $membrane->files()->delete();
        });

        static::restoring(function (Membrane $membrane) {
            // Restore related data
            foreach($membrane->datasets()->withTrashed()->get() as $ds)
                $ds->restore();
            $membrane->files()->restore();
        });

        static::forceDeleting(function (Membrane $membrane) {
            foreach($membrane->datasets()->withTrashed()->get() as $ds)
                $ds->forceDelete();
            $membrane->files()->forceDelete();
            $membrane->publications()->detach();
            $membrane->keywords()->delete();
        });
    }

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
     * Returns relative path to the membrane folder files
     */
    public static function folder()
    {
        return 'uploads/membranes/';
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

    public function files() : BelongsToMany
    {
        return $this->belongsToMany(File::class, 'model_has_files', 'model_id')
            ->wherePivot('model_type', self::class);
    }


    /**
     * References link
     */
    public function publications() : BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'model_has_publications', 'model_id', 'publication_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Membrane::class);
    }

    public static function selectOptionsGrouped($include_trashed = false)
    {
        $lastLevelGroups = Category::where('type', Category::TYPE_MEMBRANE)->without('children')->get();
        $groups = array();
        foreach($lastLevelGroups as $group)
        {
            if(!$group->membranes->count())
                continue;

            $groups[$group->getTitleHierarchy()] = $include_trashed ? 
                $group->membranes()
                    ->withTrashed()
                    ->get(['membranes.id', 'membranes.name', 'deleted_at'])
                    ->mapWithKeys(function($membrane) {
                        $name = $membrane->deleted_at ? "(DELETED) {$membrane->name}" : $membrane->name;
                        return [$membrane->id => $name];
                    })
                    ->toArray() : 
                $group->membranes->pluck('name', 'id')->toArray();
        }
        return $groups;
    }

    /**
     * Returns all assigned passive interactions
     */
    public function interactionsPassive() : HasManyThrough
    {
        return $this->hasManyThrough(InteractionPassive::class, Dataset::class);
    }

    public function interactionsActive() : HasManyThrough
    {
        return $this->hasManyThrough(InteractionActive::class, Dataset::class);
    }

    public function datasets() : HasMany
    {
        return $this->hasMany(Dataset::class);
    }
    

    /**
     * Returns all assigned keywords
     */
    public function keywords() : HasMany
    {
        return $this->hasMany(Keyword::class, 'model_id', 'id')
            ->where('model_type', Membrane::class);
    }

    /**
     * Returns assigned category
     */
    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'model_has_categories', 'model_id', 'category_id')
            ->wherePivot('model_type', Membrane::class);
    }
}
