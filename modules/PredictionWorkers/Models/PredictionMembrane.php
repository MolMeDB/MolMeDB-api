<?php

namespace Modules\PredictionWorkers\Models;

use App\Models\File;
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
        $missingFiles = [];

        foreach ($this->remotePredictionDefinitionFiles() as $file) {
            $disk = Storage::disk($file->storage);

            if ($disk->exists($file->path)) {
                return $disk->get($file->path);
            }

            $missingFiles[] = "{$file->storage}:{$file->path}";
        }

        if ($missingFiles !== []) {
            throw new RuntimeException(
                'Prediction membrane definition file(s) ['.implode(', ', $missingFiles).'] do not exist.',
            );
        }

        throw new RuntimeException("Prediction membrane {$this->getKey()} has no definition file.");
    }

    public function hasRemotePredictionDefinitionFile(): bool
    {
        foreach ($this->remotePredictionDefinitionFiles() as $file) {
            if (Storage::disk($file->storage)->exists($file->path)) {
                return true;
            }
        }

        return false;
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

    /**
     * The current file attached to the canonical Membrane is authoritative.
     * The predictions database file is retained as a fallback for legacy
     * membranes that are not mapped through remote_id.
     *
     * @return array<int, File|PredictionFile>
     */
    private function remotePredictionDefinitionFiles(): array
    {
        return collect([
            $this->membrane?->cosmoFile(),
            $this->file,
        ])->filter(
            fn (mixed $file): bool => filled($file?->storage) && filled($file?->path),
        )->unique(
            fn (mixed $file): string => $file->storage.':'.$file->path,
        )->values()->all();
    }
}
