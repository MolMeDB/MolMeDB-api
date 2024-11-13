<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubstanceIdentifierChange extends Model
{
    /** @use HasFactory<\Database\Factories\SubstanceIdentifierChangeFactory> */
    use HasFactory;

    /**
     * The previous (replaced) identifier.
     */
    public function previous(): BelongsTo
    {
        return $this->belongsTo(SubstanceIdentifier::class, 'old_id');
    }

    /**
     * The new (replaced) identifier.
     */
    public function new(): BelongsTo
    {
        return $this->belongsTo(SubstanceIdentifier::class, 'new_id');
    }

    /**
     * User, who did the change
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
