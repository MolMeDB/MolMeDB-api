<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class Dataset extends Model
{
    /** @use HasFactory<\Database\Factories\DatasetFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Dataset $dataset) {
            // Delete related data
            $dataset->interactionsActive()->delete();
            $dataset->interactionsPassive()->delete();
            $dataset->identifiers()->delete();
        });

        static::restoring(function (Dataset $dataset) {
            // Restore related data
            $dataset->interactionsActive()->restore();
            $dataset->interactionsPassive()->restore();
            $dataset->identifiers()->restore();
        });

        static::forceDeleting(function (Dataset $dataset) {
            // Force-Delete related data
            $dataset->publications()->detach();
        });
    }

    const TYPE_PASSIVE = 1;
    const TYPE_ACTIVE = 2;
    const TYPE_PASSIVE_INTERNAL_COSMO = 3;

    private static $enum_types = [
        self::TYPE_PASSIVE => 'Passive interactions',
        self::TYPE_ACTIVE => 'Active interactions',
        self::TYPE_PASSIVE_INTERNAL_COSMO => 'Internal cosmo interactions'
    ];

    public static function enumType(?int $type = null) : string|array|null
    {
        if($type)
            return isset(self::$enum_types[$type]) ? self::$enum_types[$type] : null;
        return self::$enum_types;
    }

    public function membrane() : BelongsTo
    {
        return $this->belongsTo(Membrane::class);
    }

    /**
     * Returns assigned method
     */
    public function method() : BelongsTo
    {
        return $this->belongsTo(Method::class);
    }

    /**
     * Returns assigned publication
     */
    public function publications() : BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'model_has_publications', 'model_id', 'publication_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Dataset::class);
    }

    public function identifiers() : MorphMany
    {
        return $this->morphMany(Identifier::class, 'source');
    }

    public function group() : BelongsTo
    {
        return $this->belongsTo(DatasetGroup::class, 'dataset_group_id');
    }

    /**
     * Returns record author
     */
    public function name() : ?string
    {
        return $this->name;
    }

    /**
     * Returns all related substance identifiers
     */
    public function substanceIdentifiers() : BelongsToMany
    {
        return $this->belongsToMany(Identifier::class, 'substance_identifier_dataset');
    }

    /**
     * Retuens all assigned passive interactions
     */
    public function interactionsPassive() : HasMany
    {
        return $this->hasMany(InteractionPassive::class);
    }

    public function interactionsActive() : HasMany
    {
        return $this->hasMany(InteractionActive::class);
    }

    public function isRestoreable() {
        if(!$this?->id)
        {
            return false;
        }

        return $this->membrane && $this->method;
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function getAuthorNameAttribute()
    {
        return $this->activityLogs()
            ->orderby('created_at', 'asc')
            ->first()
            ?->causer?->name;
    }
}
