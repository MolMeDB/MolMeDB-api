<?php

namespace Modules\PredictionWorkers\Models;

use App\Models\Method;
use App\Models\Publication;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionMethod extends PredictionBaseModel
{
    protected $table = 'prediction_methods';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * The "real" Method record in the default connection (same cross-database
     * convention as PredictionMembrane::membrane()/remote_id). Determines
     * which Method new interaction/dataset rows get tagged with when finished
     * predictions are imported.
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(Method::class, 'remote_id');
    }

    /**
     * Publications live in a different physical database (the default
     * connection), so this is a plain cross-connection belongsTo - Eloquent
     * resolves it as a separate query against Publication's own connection.
     */
    public function primaryPublication(): BelongsTo
    {
        return $this->belongsTo(Publication::class, 'primary_publication_id');
    }

    public function secondaryPublication(): BelongsTo
    {
        return $this->belongsTo(Publication::class, 'secondary_publication_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'method_type', 'key');
    }

    public function predictionDatasets(): HasMany
    {
        return $this->hasMany(PredictionDataset::class, 'method_type', 'key');
    }
}
