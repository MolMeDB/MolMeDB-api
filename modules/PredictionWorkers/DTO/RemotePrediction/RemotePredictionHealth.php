<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

final class RemotePredictionHealth extends RemotePredictionData
{
    public function __construct(public readonly string $status) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self((string) ($data['status'] ?? 'unknown'));
    }

    public function toArray(): array
    {
        return ['status' => $this->status];
    }

    public function isHealthy(): bool
    {
        return $this->status === 'ok';
    }
}
