<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EnumTypeLink extends Model
{
    /** @use HasFactory<\Database\Factories\EnumTypeLinkFactory> */
    use HasFactory;

    /**
     * Returns all assigned membranes
     */
    public function membranes(): BelongsToMany
    {
        return $this->belongsToMany(Membrane::class);
    }

    /**
     * Returns all assigned methods
     */
    public function methods(): BelongsToMany
    {
        return $this->belongsToMany(Method::class);
    }
}
