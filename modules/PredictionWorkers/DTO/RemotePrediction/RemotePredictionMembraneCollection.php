<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

use Illuminate\Support\Collection;

final class RemotePredictionMembraneCollection extends RemotePredictionData
{
    /**
     * @param  Collection<int, RemotePredictionMembrane>  $membranes
     */
    public function __construct(public readonly Collection $membranes) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            collect($data['membranes'] ?? [])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): RemotePredictionMembrane => RemotePredictionMembrane::fromArray($item))
                ->values(),
        );
    }

    public function toArray(): array
    {
        return [
            'membranes' => $this->membranes
                ->map(fn (RemotePredictionMembrane $membrane): array => $membrane->toArray())
                ->all(),
        ];
    }

    public function findByMd5(string $md5): ?RemotePredictionMembrane
    {
        return $this->membranes->first(
            fn (RemotePredictionMembrane $membrane): bool => hash_equals($membrane->md5, $md5),
        );
    }
}
