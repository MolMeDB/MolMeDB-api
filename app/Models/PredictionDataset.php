<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PredictionDataset extends Model
{
    /** @use HasFactory<\Database\Factories\PredictionDatasetFactory> */
    use HasFactory;

    /**
     * Returns author of the dataset
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns all runs created by this dataset
     */
    public function runs() : BelongsToMany
    {
        return $this->belongsToMany(PredictionRun::class);
    }
}
