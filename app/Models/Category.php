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
        return $this->belongsTo(Category::class);
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
}
