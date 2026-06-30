<?php

namespace Modules\PredictionWorkers\Models;

use App\Models\Structure;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionFile;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionJobSnapshot;
use Modules\PredictionWorkers\Services\RemotePrediction\RemotePredictionClient;
use RuntimeException;

class PredictionStructure extends PredictionBaseModel
{
    use Filterable;

    protected $connection = 'predictions';

    protected $table = 'structures';

    protected $guarded = [];

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'structure_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'remote_id');
    }

    public function remotePredictionStatus(
        int $eventsLimit = 30,
        ?RemotePredictionClient $client = null,
    ): RemotePredictionJobSnapshot {
        return $this->remotePredictionClient($client)->jobStatus(
            $this->remotePredictionSmiles(),
            $eventsLimit,
        );
    }

    public function downloadRemotePredictionMolecule(
        ?RemotePredictionClient $client = null,
    ): RemotePredictionFile {
        return $this->remotePredictionClient($client)->downloadMolecule($this->remotePredictionSmiles());
    }

    public function remotePredictionSmiles(): string
    {
        $smiles = trim((string) $this->canonical_smiles);

        if ($smiles === '') {
            throw new RuntimeException("Prediction structure {$this->getKey()} has no canonical SMILES.");
        }

        return $smiles;
    }

    /**
     * @deprecated Wrong relationship.
     */
    public function predictionDatasets(): HasManyThrough
    {
        return $this->hasManyThrough(
            PredictionDataset::class,
            Prediction::class,
            'id',            // Foreign key on predictions
            'id',            // Foreign key on prediction_structures
            'id',            // Local key on current model
            'structure_id'   // Local key on predictions
        );
    }

    private function remotePredictionClient(?RemotePredictionClient $client): RemotePredictionClient
    {
        return $client ?? app(RemotePredictionClient::class);
    }
}
