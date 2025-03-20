<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Protein extends Model
{
    /** @use HasFactory<\Database\Factories\ProteinFactory> */
    use HasFactory, SoftDeletes;

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
}
