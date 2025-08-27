<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StructureLink extends Model
{
    protected $guarded = [];

    public function structure() : BelongsTo {
        return $this->belongsTo(Structure::class);
    }
}
