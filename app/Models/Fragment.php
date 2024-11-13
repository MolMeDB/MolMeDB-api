<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Fragment extends Model
{
    /** @use HasFactory<\Database\Factories\FragmentFactory> */
    use HasFactory;

    /**
     * Returns all child options
     */
    public function children_options() : BelongsToMany
    {
        return $this->belongsToMany(Fragment::class, 'fragment_options', 'parent_id', 'child_id')
            ->withPivot('deletions');
    }

    /**
     * Returns all parent options
     */
    public function parent_options() : BelongsToMany
    {
        return $this->belongsToMany(Fragment::class, 'fragment_options', 'child_id', 'parent_id')
            ->withPivot('deletions');
    }

    /**
     * Returns all assigned enum types (categories)
     */
    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(EnumType::class);
    }

    /**
     * Returns parent structures
     */
    public function structures() : BelongsToMany
    {
        return $this->belongsToMany(Structure::class);
    }
}
