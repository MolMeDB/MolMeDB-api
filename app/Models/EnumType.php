<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class EnumType extends Model
{
    /** @use HasFactory<\Database\Factories\EnumTypeFactory> */
    use HasFactory;

    /**
     * Returns all direct children of this enum type.
     */
    public function children() : HasManyThrough
    {
        return $this->hasManyThrough(
            EnumType::class, 
            EnumTypeLink::class, 
            'parent_id', 
            'id', 
            'id', 
            'child_id');
    }
}
