<?php

namespace Modules\PredictionWorkers\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PredictionResult extends PredictionBaseModel
{
    protected $connection = 'predictions';
    protected $table = 'results';

    public function prediction() : HasOne
    {
        return $this->hasOne(Prediction::class, 'result_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PredictionFile::class, 'file_id');
    }
}
