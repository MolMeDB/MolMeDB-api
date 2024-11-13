<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractionPassive extends Model
{
    /** @use HasFactory<\Database\Factories\InteractionPassiveFactory> */
    use HasFactory;

    /**
     * Returns assigned substance
     */
    public function substance() : BelongsTo{
        return $this->belongsTo(Substance::class);
    }

    /**
     * Returns assigned membrane
     */
    public function membrane() : BelongsTo{
        return $this->belongsTo(Membrane::class);
    }

    /**
     * Returns assigned method
     */
    public function method() : BelongsTo{
        return $this->belongsTo(Method::class);
    }

    /**
     * Returns assigned dataset
     */
    public function dataset() : BelongsTo{
        return $this->belongsTo(Dataset::class);
    }

    /**
     * Returns assigned publication
     */
    public function publication() : BelongsTo{
        return $this->belongsTo(Publication::class);
    }
    
    /**
     * Returns assigned structure ion
     */
    public function ion() : BelongsTo{
        return $this->belongsTo(StructureIon::class);
    }
}
