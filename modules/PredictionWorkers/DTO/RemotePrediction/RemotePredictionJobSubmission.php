<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

use Modules\PredictionWorkers\Enums\RemotePredictionStatus;

final class RemotePredictionJobSubmission extends RemotePredictionData
{
    public function __construct(
        public readonly string $moleculeId,
        public readonly string $calculationId,
        public readonly string $canonicalSmiles,
        public readonly RemotePredictionStatus|string $moleculeStatus,
        public readonly RemotePredictionStatus|string $calculationStatus,
        public readonly string $message,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $moleculeStatus = (string) ($data['molecule_status'] ?? '');
        $calculationStatus = (string) ($data['calculation_status'] ?? '');

        return new self(
            moleculeId: (string) ($data['molecule_id'] ?? ''),
            calculationId: (string) ($data['calculation_id'] ?? ''),
            canonicalSmiles: (string) ($data['canonical_smiles'] ?? ''),
            moleculeStatus: RemotePredictionStatus::tryFrom($moleculeStatus) ?? $moleculeStatus,
            calculationStatus: RemotePredictionStatus::tryFrom($calculationStatus) ?? $calculationStatus,
            message: (string) ($data['message'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'molecule_id' => $this->moleculeId,
            'calculation_id' => $this->calculationId,
            'canonical_smiles' => $this->canonicalSmiles,
            'molecule_status' => $this->moleculeStatus instanceof RemotePredictionStatus
                ? $this->moleculeStatus->value
                : $this->moleculeStatus,
            'calculation_status' => $this->calculationStatus instanceof RemotePredictionStatus
                ? $this->calculationStatus->value
                : $this->calculationStatus,
            'message' => $this->message,
        ];
    }
}
