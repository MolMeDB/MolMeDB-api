<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionRun extends Model
{
    /** @use HasFactory<\Database\Factories\PredictionRunFactory> */
    use HasFactory;

    /**
     * Returns assigned structure
     */
    public function structure() : BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    /**
     * Returns assigned membrane
     */
    public function membrane() : BelongsTo
    {
        return $this->belongsTo(Membrane::class);
    }

    /**
     * Returns all dataset assigned with this setting
     */
    public function datasets() : BelongsToMany
    {
        return $this->belongsToMany(PredictionDataset::class);
    }

    /**
     * Returns all assigned logs
     */
    public function logs() : HasMany
    {
        return $this->hasMany(PredictionRunLog::class);
    }
}
