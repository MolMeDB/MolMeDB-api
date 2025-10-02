<?php
namespace Modules\PredictionWorkers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionMembrane extends PredictionBaseModel
{
    protected $connection = 'predictions';
    protected $table = 'membranes';

    public function predictions() : HasMany
    {
        return $this->hasMany(Prediction::class, 'membrane_id');
    }

    public function predictionDatasets() : HasMany
    {
        return $this->hasMany(PredictionDataset::class, 'membrane_id');
    }

    public function membrane() : BelongsTo
    {
        return $this->belongsTo(\App\Models\Membrane::class, 'remote_id');
    }

    public function file() : BelongsTo
    {
        return $this->belongsTo(PredictionFile::class);
    }
}