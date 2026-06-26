<?php

namespace Modules\PredictionWorkers\Models;

use App\Models\Membrane;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionMembrane;
use Modules\PredictionWorkers\Exceptions\RemotePredictionException;
use Modules\PredictionWorkers\Services\RemotePrediction\RemotePredictionClient;
use RuntimeException;

class PredictionMembrane extends PredictionBaseModel
{
    protected $connection = 'predictions';

    protected $table = 'membranes';

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'membrane_id');
    }

    public function predictionDatasets(): HasMany
    {
        return $this->hasMany(PredictionDataset::class, 'membrane_id');
    }

    public function membrane(): BelongsTo
    {
        return $this->belongsTo(Membrane::class, 'remote_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PredictionFile::class);
    }

    public function remotePredictionContent(): string
    {
        $file = $this->file;

        if ($file?->storage && $file->path) {
            $disk = Storage::disk($file->storage);

            if (! $disk->exists($file->path)) {
                throw new RuntimeException("Prediction membrane file [{$file->path}] does not exist.");
            }

            return $disk->get($file->path);
        }

        $cosmoFile = $this->membrane?->cosmoFile();

        if ($cosmoFile?->storage && $cosmoFile->path) {
            $disk = Storage::disk($cosmoFile->storage);

            if (! $disk->exists($cosmoFile->path)) {
                throw new RuntimeException("Membrane COSMO file [{$cosmoFile->path}] does not exist.");
            }

            return $disk->get($cosmoFile->path);
        }

        throw new RuntimeException("Prediction membrane {$this->getKey()} has no definition file.");
    }

    public function hasRemotePredictionDefinitionFile(): bool
    {
        if (filled($this->file?->storage) && filled($this->file?->path)) {
            return true;
        }

        $cosmoFile = $this->membrane?->cosmoFile();

        return filled($cosmoFile?->storage) && filled($cosmoFile?->path);
    }

    public function findRemotePredictionMembrane(
        ?RemotePredictionClient $client = null,
    ): ?RemotePredictionMembrane {
        $contentMd5 = md5($this->remotePredictionContent());

        return $this->remotePredictionClient($client)
            ->membranes()
            ->findByMd5($contentMd5);
    }

    public function findRemotePredictionKey(?RemotePredictionClient $client = null): ?string
    {
        return $this->findRemotePredictionMembrane($client)?->key;
    }

    public function uploadToRemotePrediction(
        ?string $key = null,
        ?RemotePredictionClient $client = null,
    ): RemotePredictionMembrane {
        return $this->remotePredictionClient($client)->uploadMembrane(
            $key ?? $this->defaultRemotePredictionKey(),
            $this->remotePredictionContent(),
        );
    }

    public function ensureRemotePredictionKey(
        ?string $preferredKey = null,
        ?RemotePredictionClient $client = null,
    ): string {
        if (! $this->hasRemotePredictionDefinitionFile()) {
            return $preferredKey ?? $this->defaultRemotePredictionKey();
        }

        $client = $this->remotePredictionClient($client);
        $existing = $this->findRemotePredictionMembrane($client);

        if ($existing) {
            if (! $existing->fileValid) {
                throw new RuntimeException(
                    "Remote prediction membrane [{$existing->key}] exists, but its remote file failed validation.",
                );
            }

            return $existing->key;
        }

        try {
            return $this->uploadToRemotePrediction($preferredKey, $client)->key;
        } catch (RemotePredictionException $exception) {
            if (
                $exception->errorCode === 'duplicate_membrane_content'
                && is_array($exception->detail)
                && isset($exception->detail['existing_key'])
            ) {
                return (string) $exception->detail['existing_key'];
            }

            throw $exception;
        }
    }

    public function defaultRemotePredictionKey(): string
    {
        $base = Str::slug((string) ($this->abbreviation ?: $this->name), '-');

        return Str::limit(
            $base !== '' ? $base : "membrane-{$this->getKey()}",
            128,
            '',
        );
    }

    private function remotePredictionClient(?RemotePredictionClient $client): RemotePredictionClient
    {
        return $client ?? app(RemotePredictionClient::class);
    }
}
