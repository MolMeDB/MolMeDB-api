<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dataset extends Model
{
    /** @use HasFactory<\Database\Factories\DatasetFactory> */
    use HasFactory;

    /**
     * Return assigned membrane
     */
    public function membrane() : BelongsTo
    {
        return $this->belongsTo(Membrane::class);
    }

    /**
     * Returns assigned method
     */
    public function method() : BelongsTo
    {
        return $this->belongsTo(Method::class);
    }

    /**
     * Returns assigned publication
     */
    public function publication() : BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    /**
     * Returns record author
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns all related substance identifiers
     */
    public function substanceIdentifiers() : BelongsToMany
    {
        return $this->belongsToMany(SubstanceIdentifier::class, 'substance_identifier_dataset');
    }

    /**
     * Retuens all assigned passive interactions
     */
    public function interactionsPassive() : HasMany
    {
        return $this->hasMany(InteractionPassive::class);
    }
}
