<?php
namespace Modules\PredictionWorkers\DTO;

use JsonSerializable;

class SoluteResult implements JsonSerializable
{
    public function __construct(
        public float $meanPosition,
        public float $logK,
        public ?float $logPerm,
        public array $energyValues
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'mean_position' => $this->meanPosition,
            'logK' => $this->logK,
            'logPerm' => $this->logPerm,
            'energy_values' => $this->energyValues,
        ];
    }
}