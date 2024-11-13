<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FragmentOption extends Model
{
    /** @use HasFactory<\Database\Factories\FragmentOptionFactory> */
    use HasFactory;

    /**
     * Returns parent fragment
     */
    public function parent() : BelongsTo
    {
        return $this->belongsTo(Fragment::class, 'parent_id');
    }

    /**
     * Returns child fragment
     */
    public function child() : BelongsTo
    {
        return $this->belongsTo(Fragment::class, 'child_id');
    }
}
