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

    const TYPE_MEMBRANE = 1;
    const TYPE_METHOD = 2;
    const TYPE_PROTEIN = 3;
    const TYPE_ACTIVE_INTERACTION = 4;

    private static $enumTypes = array
    (
        self::TYPE_MEMBRANE => 'Membrane',
        self::TYPE_METHOD => 'Method',
        self::TYPE_PROTEIN => 'Protein',
        self::TYPE_ACTIVE_INTERACTION => 'Active Interaction'
    );

    public static function enumType($type) : string | null | array
    {
        if($type === null)
            return self::$enumTypes;
        return isset(self::$enumTypes[$type]) ? self::$enumTypes[$type] : null;
    }

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
    public function membranes() : BelongsToMany
    {
        return $this->belongsToMany(Membrane::class, 'model_has_categories', 'category_id', 'model_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Membrane::class);
    }

    /**
     * Returns all assigned methods to current category
     */
    public function methods() : BelongsToMany
    {
        return $this->belongsToMany(Method::class, 'model_has_categories', 'category_id', 'model_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Method::class);
    }

    public function interactionsActive() : HasMany
    {
        return $this->hasMany(InteractionActive::class);
    }

    /**
     * Checks, if category can be deleted
     */
    public function isDeletable() : bool
    {
        return $this->membranes()->count() === 0 && 
            $this->methods()->count() === 0 && 
            $this->interactionsActive()->count() === 0;
    }

    /**
     * Checks, if category has children
     */
    public function hasChildren() : bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Returns categories hierarchy
     * 
     * @return array
     */
    public function getTitleHierarchy() : string | null
    {
        $result = '';
        $record = $this;

        if(!$record?->id)
            return null;

        $result = $record->title;

        while($record->parent)
        {
            $result = $record->parent->title . ' » ' . $result;
            $record = $record->parent;
        }
        return $result;
    }
}
