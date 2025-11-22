<?php

namespace Modules\PredictionWorkers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PredictionDataset extends PredictionBaseModel
{
    protected $connection = 'predictions';
    protected $table = 'datasets';

    protected $guarded = [];

    public function predictions() : BelongsToMany
    {
        return $this->belongsToMany(Prediction::class, 'prediction_has_datasets', 'dataset_id', 'prediction_id')
            ->withTimestamps();
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public static function method($method) : string
    {
        return Prediction::$enum_methods[$method];
    }

    public static function enum_priority($priority) : string
    {
        return Prediction::$enum_priorities[$priority];
    }

    public function predictionMembrane() : BelongsTo
    {
        return $this->belongsTo(PredictionMembrane::class, 'membrane_id');
    }

    public function membrane() : BelongsTo
    {
        return $this->predictionMembrane->membrane();
    }

    public function predictionStructures() : HasManyThrough
    {
        return $this->hasManyThrough(
            PredictionStructure::class,
            Prediction::class,
            'id',            // Foreign key on predictions
            'id',            // Foreign key on prediction_structures
            'id',            // Local key on current model
            'structure_id'   // Local key on predictions
        );
    }
}
