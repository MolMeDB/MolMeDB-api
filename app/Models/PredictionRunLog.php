<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionRunLog extends Model
{
    /** @use HasFactory<\Database\Factories\PredictionRunLogFactory> */
    use HasFactory;

    /**
     * Returns assigned run
     */
    public function run() : BelongsTo
    {
        return $this->belongsTo(PredictionRun::class);
    }
}
