<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FluorescentProperty extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function structure() : BelongsTo 
    {
        return $this->belongsTo(Structure::class);
    }
}