<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

final class RemotePredictionStatistics extends RemotePredictionData
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public readonly array $data) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(RemotePredictionPayload::parse($data));
    }

    public function toArray(): array
    {
        return RemotePredictionPayload::serialize($this->data);
    }
}
