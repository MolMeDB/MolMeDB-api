<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Method extends Model
{
    /** @use HasFactory<\Database\Factories\MethodFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Method $method) {
            // Delete related data
            foreach($method->datasets as $ds)
                $ds->delete();
            $method->files()->delete();
        });

        static::restoring(function (Method $method) {
            // Restore related data
            foreach($method->datasets()->withTrashed()->get() as $ds)
                $ds->restore();
            $method->files()->restore();
        });

        static::forceDeleting(function (Method $method) {
            // Force-Delete related data
            foreach($method->datasets()->withTrashed()->get() as $ds)
                $ds->forceDelete();
            $method->files()->forceDelete();
            $method->publications()->detach();
            $method->keywords()->delete();
        });
    }

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

    public static function folder()
    {
        return 'uploads/methods/';
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

    public static function selectOptionsGrouped($include_trashed = false)
    {
        $lastLevelGroups = Category::where('type', Category::TYPE_METHOD)->without('children')->get();
        $groups = array();
        foreach($lastLevelGroups as $group)
        {
            if(!$group->methods->count())
                continue;

            $groups[$group->getTitleHierarchy()] = $include_trashed ? 
                $group->methods()
                    ->withTrashed()
                    ->get(['methods.id', 'methods.name', 'deleted_at'])
                    ->mapWithKeys(function($method) {
                        $name = $method->deleted_at ? "(DELETED) {$method->name}" : $method->name;
                        return [$method->id => $name];
                    })
                    ->toArray() : 
                $group->methods->pluck('name', 'id')->toArray();
        }
        return $groups;
    }

    /**
     * References link
     */
    public function publications() : BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'model_has_publications', 'model_id', 'publication_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Method::class);
    }

    public function datasets() : HasMany
    {
        return $this->hasMany(Dataset::class);
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

    /**
     * Returns all assigned keywords
     */
    public function keywords() : HasMany
    {
        return $this->hasMany(Keyword::class, 'model_id', 'id')
            ->where('model_type', Method::class);
    }

    /**
     * Returns assigned category
     */
    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'model_has_categories', 'model_id', 'category_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Method::class);
    }
}
