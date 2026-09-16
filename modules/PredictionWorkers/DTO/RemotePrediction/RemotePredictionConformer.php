<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\PredictionWorkers\Enums\RemotePredictionStatus;
use Modules\PredictionWorkers\Enums\RemotePredictionStep;

final class RemotePredictionConformer extends RemotePredictionData
{
    /**
     * @param  Collection<int, RemotePredictionStepSnapshot>  $steps
     */
    public function __construct(
        public readonly string $id,
        public readonly int $index,
        public readonly ?float $relativeEnergyKcalMol,
        public readonly ?string $sourceXyz,
        public readonly RemotePredictionStatus|string $status,
        public readonly RemotePredictionStep|string|null $currentStep,
        public readonly ?string $workDir,
        public readonly Collection $steps,
        public readonly ?CarbonImmutable $createdAt,
        public readonly ?CarbonImmutable $updatedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $status = (string) ($data['status'] ?? '');
        $currentStep = isset($data['current_step']) ? (string) $data['current_step'] : null;

        return new self(
            id: (string) ($data['id'] ?? ''),
            index: (int) ($data['conformer_index'] ?? 0),
            relativeEnergyKcalMol: isset($data['relative_energy_kcal_mol'])
                ? (float) $data['relative_energy_kcal_mol']
                : null,
            sourceXyz: isset($data['source_xyz']) ? (string) $data['source_xyz'] : null,
            status: RemotePredictionStatus::tryFrom($status) ?? $status,
            currentStep: $currentStep === null
                ? null
                : (RemotePredictionStep::tryFrom($currentStep) ?? $currentStep),
            workDir: isset($data['work_dir']) ? (string) $data['work_dir'] : null,
            steps: collect($data['steps'] ?? [])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): RemotePredictionStepSnapshot => RemotePredictionStepSnapshot::fromArray($item))
                ->values(),
            createdAt: self::date($data['created_at'] ?? null),
            updatedAt: self::date($data['updated_at'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'conformer_index' => $this->index,
            'relative_energy_kcal_mol' => $this->relativeEnergyKcalMol,
            'source_xyz' => $this->sourceXyz,
            'status' => $this->status instanceof RemotePredictionStatus ? $this->status->value : $this->status,
            'current_step' => $this->currentStep instanceof RemotePredictionStep
                ? $this->currentStep->value
                : $this->currentStep,
            'work_dir' => $this->workDir,
            'steps' => $this->steps
                ->map(fn (RemotePredictionStepSnapshot $step): array => $step->toArray())
                ->all(),
            'created_at' => $this->createdAt?->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }
}
