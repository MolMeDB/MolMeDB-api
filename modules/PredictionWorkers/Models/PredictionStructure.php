<?php
namespace Modules\PredictionWorkers\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\PredictionWorkers\Traits\HasRemoteConformers;

class PredictionStructure extends PredictionBaseModel
{
    use HasRemoteConformers, Filterable;

    protected $connection = 'predictions';
    protected $table = 'structures';

    protected $guarded = [];

    public function predictions() : HasMany
    {
        return $this->hasMany(Prediction::class, 'structure_id');
    }

    public function structure() : BelongsTo
    {
        return $this->belongsTo(\App\Models\Structure::class, 'remote_id');
    }

    /**
     * @deprecated
     * 
     * Wrong relationship 
     */
    public function predictionDatasets() : HasManyThrough
    {
        return $this->hasManyThrough(
            PredictionDataset::class,
            Prediction::class,
            'id',            // Foreign key on predictions
            'id',            // Foreign key on prediction_structures
            'id',            // Local key on current model
            'structure_id'   // Local key on predictions
        );
    }
}