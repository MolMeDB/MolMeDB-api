<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Structure extends Model
{
    /** @use HasFactory<\Database\Factories\StructureFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * Returns assigned substance
     */
    public function substance() : BelongsTo
    {
        return $this->belongsTo(Substance::class);
    }

    /**
     * Returns assigned ions
     */
    public function ions() : HasMany
    {
        return $this->hasMany(StructureIon::class);
    }
}
