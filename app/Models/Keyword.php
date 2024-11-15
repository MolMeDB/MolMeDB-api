<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Keyword extends Model
{
    /** @use HasFactory<\Database\Factories\KeywordFactory> */
    use HasFactory;
    // No timestamps
    public $timestamps = false;

    protected $guarded = [];

    /**
     * Returns all membranes assigned to the current keyword
     */
    // public function membranes() : BelongsToMany
    // {
    //     return $this->belongsToMany(Membrane::class);
    // }
}
