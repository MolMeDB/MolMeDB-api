<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractionActive extends Model
{
    /** @use HasFactory<\Database\Factories\InteractionActiveFactory> */
    use HasFactory;

    /**
     * Returns assigned dataset
     */
    public function dataset() : BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    /**
     * Returns assigned structure ion
     */
    public function ion() : BelongsTo
    {
        return $this->belongsTo(StructureIon::class);
    }

    /**
     * Returns assigned substance
     */
    public function substance() : BelongsTo
    {
        return $this->belongsTo(Substance::class);
    }

    /**
     * Returns assigned protein
     */
    public function protein() : BelongsTo
    {
        return $this->belongsTo(Protein::class);
    }
}
