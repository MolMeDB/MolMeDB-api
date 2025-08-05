<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Protein extends Model
{
    /** @use HasFactory<\Database\Factories\ProteinFactory> */
    use HasFactory, SoftDeletes;
    use Filterable;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Protein $protein) {
            // Delete related data
            $protein->interactionsActive()->delete();
        });

        static::restoring(function (Protein $protein) {
            // Restore related data
            $protein->interactionsActive()->restore();
        });

        static::forceDeleting(function (Protein $protein) {
            $protein->interactionsActive()->forceDelete();
        });
    }

    /**
     * Returns all assigned active interactions
     */
    public function interactionsActive() : HasMany
    {
        return $this->hasMany(InteractionActive::class);
    }

    public function structures() : BelongsToMany {
        return $this->belongsToMany(Structure::class, 'interactions_active', 'protein_id', 'structure_id')
            ->distinct();
    }

    public function identifiers() : HasMany {
        return $this->hasMany(ProteinIdentifier::class);
    }

    /**
     * Returns assigned category
     */
    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'model_has_categories', 'model_id', 'category_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Protein::class);
    }
}
