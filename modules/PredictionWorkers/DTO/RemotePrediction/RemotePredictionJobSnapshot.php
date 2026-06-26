<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\PredictionWorkers\Enums\RemotePredictionStatus;
use Modules\PredictionWorkers\Enums\RemotePredictionStep;

final class RemotePredictionJobSnapshot extends RemotePredictionData
{
    /**
     * @param  Collection<int, RemotePredictionStepSnapshot>  $steps
     * @param  Collection<int, RemotePredictionConformer>  $conformers
     * @param  Collection<int, RemotePredictionCalculation>  $calculations
     * @param  Collection<int, array<string, mixed>>  $events
     * @param  array<string, mixed>|null  $requeue
     */
    public function __construct(
        public readonly string $id,
        public readonly string $canonicalSmiles,
        public readonly RemotePredictionStatus|string $status,
        public readonly RemotePredictionStep|string|null $currentStep,
        public readonly ?string $workDir,
        public readonly int $conformerCount,
        public readonly Collection $steps,
        public readonly Collection $conformers,
        public readonly Collection $calculations,
        public readonly Collection $events,
        public readonly ?array $requeue,
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
            canonicalSmiles: (string) ($data['canonical_smiles'] ?? ''),
            status: RemotePredictionStatus::tryFrom($status) ?? $status,
            currentStep: $currentStep === null
                ? null
                : (RemotePredictionStep::tryFrom($currentStep) ?? $currentStep),
            workDir: isset($data['work_dir']) ? (string) $data['work_dir'] : null,
            conformerCount: (int) ($data['conformer_count'] ?? 0),
            steps: collect($data['steps'] ?? [])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): RemotePredictionStepSnapshot => RemotePredictionStepSnapshot::fromArray($item))
                ->values(),
            conformers: collect($data['conformers'] ?? [])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): RemotePredictionConformer => RemotePredictionConformer::fromArray($item))
                ->values(),
            calculations: collect($data['calculations'] ?? [])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): RemotePredictionCalculation => RemotePredictionCalculation::fromArray($item))
                ->values(),
            events: collect($data['events'] ?? [])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): array => RemotePredictionPayload::parse($item))
                ->values(),
            requeue: is_array($data['requeue'] ?? null)
                ? RemotePredictionPayload::parse($data['requeue'])
                : null,
            createdAt: self::date($data['created_at'] ?? null),
            updatedAt: self::date($data['updated_at'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'canonical_smiles' => $this->canonicalSmiles,
            'status' => $this->status instanceof RemotePredictionStatus ? $this->status->value : $this->status,
            'current_step' => $this->currentStep instanceof RemotePredictionStep
                ? $this->currentStep->value
                : $this->currentStep,
            'work_dir' => $this->workDir,
            'conformer_count' => $this->conformerCount,
            'steps' => $this->steps
                ->map(fn (RemotePredictionStepSnapshot $step): array => $step->toArray())
                ->all(),
            'conformers' => $this->conformers
                ->map(fn (RemotePredictionConformer $conformer): array => $conformer->toArray())
                ->all(),
            'calculations' => $this->calculations
                ->map(fn (RemotePredictionCalculation $calculation): array => $calculation->toArray())
                ->all(),
            'events' => RemotePredictionPayload::serialize($this->events->all()),
            'requeue' => RemotePredictionPayload::serialize($this->requeue),
            'created_at' => $this->createdAt?->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }

    public function calculationFor(string $membraneKey, float $temperatureC): ?RemotePredictionCalculation
    {
        return $this->calculations->first(
            fn (RemotePredictionCalculation $calculation): bool => $calculation->membraneKey === $membraneKey
                && abs($calculation->temperatureC - $temperatureC) < 0.0001,
        );
    }

    public function calculationById(string $calculationId): ?RemotePredictionCalculation
    {
        return $this->calculations->first(
            fn (RemotePredictionCalculation $calculation): bool => $calculation->id === $calculationId,
        );
    }
}
