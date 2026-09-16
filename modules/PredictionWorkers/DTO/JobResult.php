<?php

namespace Modules\PredictionWorkers\DTO;

use JsonSerializable;

class JobResult implements JsonSerializable
{
    public function __construct(
        public string $jobNumber,
        public string $symmetry,
        public int $layerCount,
        public array $layerPositions,
        public float $temperature,
        public array $solutes // SoluteResult[]
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'symmetry' => $this->symmetry,
            'layer_count' => $this->layerCount,
            'layer_positions' => $this->layerPositions,
            'temperature' => $this->temperature,
            'solutes' => array_map(fn ($s) => $s->jsonSerialize(), $this->solutes),
        ];
    }
}