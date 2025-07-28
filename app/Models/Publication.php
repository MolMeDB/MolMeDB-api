<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Publication extends Model
{
    /** @use HasFactory<\Database\Factories\PublicationFactory> */
    use HasFactory, SoftDeletes, LogsActivity;
    use Filterable;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Publication $publication) {
            // Delete related data
            foreach($publication->datasets as $ds)
                $ds->delete();
            $publication->interactionsActive()->delete();
            $publication->interactionsPassive()->delete();
        });

        static::restoring(function (Publication $publication) {
            // Restore related data
            foreach($publication->datasets()->withTrashed()->get() as $ds)
                $ds->restore();
            $publication->interactionsActive()->restore();
            $publication->interactionsPassive()->restore();
        });

        static::forceDeleting(function (Publication $publication) {
            foreach($publication->datasets()->withTrashed()->get() as $ds)
                $ds->forceDelete();
            $publication->membranes()->withTrashed()->get()->detach();
            $publication->methods()->withTrashed()->get()->detach();
            $publication->interactionsActive()->forceDelete();
            $publication->interactionsPassive()->forceDelete();
        });
    }

    /**
     * TYPES
     */
    const TYPE_PUBCHEM = 1;
    const TYPE_CHEMBL = 2;
    const TYPE_COSMO = 3;

    private static $enum_types = [
        self::TYPE_PUBCHEM => 'Pubchem',
        self::TYPE_CHEMBL => 'ChEMBL',
        self::TYPE_COSMO => 'COSMO'
    ];

    public static function types() : array
    {
        return self::$enum_types;
    }

    /**
     * Returns enum type
     */
    public static function enumType($type) : ?string 
    {
        if(isset(self::$enum_types[$type]))
            return self::$enum_types[$type];
        return null;
    }

    public function getSelectTitle() : string 
    {
        return "[$this->id]: $this->citation";
    }

    /**
     * Returns all assigned membranes, which are described in the current publication
     */
    public function membranes(): BelongsToMany
    {
        return $this->belongsToMany(Membrane::class, 'model_has_publications', 'publication_id', 'model_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Membrane::class);
    }

    /**
     * Returns all assigned methods, which are described in the current publication
     */
    public function methods(): BelongsToMany
    {
        return $this->belongsToMany(Method::class, 'model_has_publications', 'publication_id', 'model_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Method::class);
    }

    public function datasets(): BelongsToMany
    {
        return $this->belongsToMany(Dataset::class, 'model_has_publications', 'publication_id', 'model_id')
            ->withPivot('model_type', 'model_id')
            ->wherePivot('model_type', Dataset::class);
    }

    public function interactionsPassive() : HasMany
    {
        return $this->hasMany(InteractionPassive::class);
    }
    public function interactionsActive() : HasMany
    {
        return $this->hasMany(InteractionActive::class);
    }

    public function authors() : BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'publication_has_authors');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logOnlyDirty();
    }
}
