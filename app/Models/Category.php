<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SolutionForest\FilamentTree\Concern\ModelTree;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, ModelTree;

    protected $guarded = [];

    protected $casts = [
        'parent_id' => 'int'
    ];

    /**
     * TYPES
     */
    const TYPE_MEMBRANE = 1;
    const TYPE_METHOD = 2;

    /**
     * Returns parent category
     */
    public function parent() : BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Returns children categories
     */
    public function children() : HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Returns all assigned membranes to current category
     */
    public function membranes() : HasMany
    {
        return $this->hasMany(Membrane::class);
    }

    /**
     * Returns all assigned methods to current category
     */
    public function methods() : HasMany
    {
        return $this->hasMany(Method::class);
    }

    /**
     * Checks, if category can be deleted
     */
    public function isDeletable() : bool
    {
        return $this->membranes()->count() === 0 && $this->methods()->count() === 0;
    }

    /**
     * Checks, if category has children
     */
    public function hasChildren() : bool
    {
        return $this->children()->count() > 0;
    }
}
