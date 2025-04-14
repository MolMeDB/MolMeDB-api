<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    /** @use HasFactory<\Database\Factories\AuthorsFactory> */
    use HasFactory;
    public $timestamps = false;

    protected $guarded = [];

    public function publications() : BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'publication_has_authors');
    }
}
