<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StructureIon extends Model
{
    /** @use HasFactory<\Database\Factories\StructureIonFactory> */
    use HasFactory;

    /**
     * Returns parent structure
     */
    public function structure() : BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
