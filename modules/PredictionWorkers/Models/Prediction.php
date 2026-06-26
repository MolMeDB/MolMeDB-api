<?php

namespace Modules\PredictionWorkers\Models;

use Carbon\CarbonInterface;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionCalculation;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionFile;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionJobSnapshot;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionJobSubmission;
use Modules\PredictionWorkers\Enums\RemotePredictionArtifact;
use Modules\PredictionWorkers\Enums\RemotePredictionStatus;
use Modules\PredictionWorkers\Enums\RemotePredictionStep;
use Modules\PredictionWorkers\Exceptions\RemotePredictionException;
use Modules\PredictionWorkers\Services\RemotePrediction\RemotePredictionClient;
use RuntimeException;

class Prediction extends PredictionBaseModel
{
    use Filterable;

    const PRIORITY_LOW = 1;

    const PRIORITY_MEDIUM = 2;

    const PRIORITY_HIGH = 3;

    /** STATES */
    const STATE_STOPPED = 0;

    const STATE_PREPARED = 1;

    const STATE_ERROR = 2;

    const STATE_REMOVE = 3;

    const STATE_RUNNING = 4;

    const STATE_FINISHED = 5;

    /** COSMO STEPS */
    const STEP_PENDING = 0;

    const STEP_IONIZED = 1;

    const STEP_SDF_READY = 2;

    const STEP_OPTIMIZATION = 3;

    const STEP_COSMO = 4;

    const STEP_RESULT_DOWNLOAD = 5;

    const STEP_RESULT_PARSE = 6;

    const STEP_RESULT_DB_STORE = 7;

    /** METHODS */
    const METHOD_COSMOPERM = 'cosmoperm';

    const METHOD_COSMOMIC = 'cosmomic';

    public static $enum_methods = [
        self::METHOD_COSMOMIC => 'CosmoMic',
        self::METHOD_COSMOPERM => 'CosmoPerm',
    ];

    public static $enum_method_shorts = [
        self::METHOD_COSMOMIC => 'mic',
        self::METHOD_COSMOPERM => 'perm',
    ];

    public static $enum_steps = [
        self::STEP_PENDING => 'Pending',
        self::STEP_IONIZED => 'Ionized',
        self::STEP_SDF_READY => 'Make SDF',
        self::STEP_OPTIMIZATION => 'Structure optimization',
        self::STEP_COSMO => 'COSMO prediction',
        self::STEP_RESULT_DOWNLOAD => 'Result prepared for parsing',
        self::STEP_RESULT_PARSE => 'Result Parsed',
        self::STEP_RESULT_DB_STORE => 'Result Stored',
    ];

    public static $enum_states = [
        self::STATE_STOPPED => 'Stopped',
        self::STATE_PREPARED => 'Prepared',
        self::STATE_RUNNING => 'Running',
        self::STATE_FINISHED => 'Finished',
        self::STATE_ERROR => 'Error',
        self::STATE_REMOVE => 'Remove',
    ];

    public static $enum_priorities = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_MEDIUM => 'Medium',
        self::PRIORITY_HIGH => 'High',
    ];

    public static $enum_remote_statuses = [
        'pending' => 'Pending',
        'queued' => 'Queued',
        'running' => 'Running',
        'waiting_for_conformers' => 'Preparing conformers',
        'waiting_for_script' => 'Waiting for sources',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ];

    public static function finalStep(): int
    {
        return self::STEP_RESULT_DB_STORE;
    }

    /**
     * @return array<int>
     */
    public static function failedStates(): array
    {
        return [
            self::STATE_ERROR,
            self::STATE_REMOVE,
            self::STATE_STOPPED,
        ];
    }

    public static function progressStepValue(?int $step, ?int $state = null, mixed $resultId = null): int
    {
        if ($resultId !== null) {
            return self::finalStep();
        }

        if ($step === null || $step < 0) {
            return 0;
        }

        if ($step >= self::finalStep()) {
            return self::finalStep();
        }

        return min($step, self::finalStep());
    }

    public static function progressPercent(?int $step, ?int $state = null, mixed $resultId = null): int
    {
        if (self::finalStep() === 0) {
            return 0;
        }

        return (int) round((self::progressStepValue($step, $state, $resultId) / self::finalStep()) * 100);
    }

    public static function enumMethod($method): string
    {
        return self::$enum_methods[$method];
    }

    public static function enumStep($step): string
    {
        return self::$enum_steps[$step] ?? 'N/A';
    }

    public static function enumState($state): string
    {
        return self::$enum_states[$state] ?? 'N/A';
    }

    public static function enumRemoteStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return self::$enum_remote_statuses[$status] ?? $status;
    }

    public static function enumPriority($priority): string
    {
        return self::$enum_priorities[$priority] ?? 'N/A';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'logs' => 'array',
            'remote_submitted_at' => 'datetime',
            'remote_heartbeat_at' => 'datetime',
            'remote_last_status_at' => 'datetime',
            'remote_finished_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function remotePredictionMethodOptions(): array
    {
        return collect(config('prediction-workers.remote.methods', []))
            ->filter(fn (mixed $definition): bool => is_array($definition)
                && filled($definition['remote_method'] ?? null))
            ->mapWithKeys(fn (array $definition, string $methodType): array => [
                $methodType => (string) ($definition['label'] ?? self::$enum_methods[$methodType] ?? $methodType),
            ])
            ->all();
    }

    public static function hasRemotePredictionMethod(?string $methodType): bool
    {
        return $methodType !== null
            && filled(config("prediction-workers.remote.methods.{$methodType}.remote_method"));
    }

    public function remotePredictionMethod(): string
    {
        $methodType = (string) $this->method_type;
        $remoteMethod = (string) config("prediction-workers.remote.methods.{$methodType}.remote_method", '');

        if ($remoteMethod === '') {
            throw new RuntimeException("Prediction method {$methodType} has no remote prediction mapping.");
        }

        return $remoteMethod;
    }

    public function predictionDatasets(): BelongsToMany
    {
        return $this->belongsToMany(PredictionDataset::class, 'prediction_has_datasets', 'prediction_id', 'dataset_id')
            ->withTimestamps();
    }

    public function predictionResult(): BelongsTo
    {
        return $this->belongsTo(PredictionResult::class, 'result_id');
    }

    public function predictionStructure(): BelongsTo
    {
        return $this->belongsTo(PredictionStructure::class, 'structure_id');
    }

    public function predictionMembrane(): BelongsTo
    {
        return $this->belongsTo(PredictionMembrane::class, 'membrane_id');
    }

    public function submitToRemotePrediction(
        ?string $preferredMembraneKey = null,
        ?RemotePredictionClient $client = null,
    ): RemotePredictionJobSubmission {
        $client = $this->remotePredictionClient($client);
        $membraneKey = $this->remotePredictionMembraneKey($preferredMembraneKey, $client);

        try {
            return $client->createJob(
                $this->remotePredictionSmiles(),
                $membraneKey,
                (float) $this->temperature,
                $this->remotePredictionMethod(),
            );
        } catch (RemotePredictionException $e) {
            if (! str_contains(strtolower($e->getMessage()), 'unknown membrane key')) {
                throw $e;
            }

            $membrane = $this->predictionMembrane;

            if (! $membrane?->hasRemotePredictionDefinitionFile()) {
                throw $e;
            }

            $membraneKey = $membrane->uploadToRemotePrediction($preferredMembraneKey, $client)->key;

            return $client->createJob(
                $this->remotePredictionSmiles(),
                $membraneKey,
                (float) $this->temperature,
                $this->remotePredictionMethod(),
            );
        }
    }

    public function submitAndStoreRemotePrediction(
        ?string $preferredMembraneKey = null,
        ?RemotePredictionClient $client = null,
    ): RemotePredictionJobSubmission {
        $submission = $this->submitToRemotePrediction($preferredMembraneKey, $client);

        $this->forceFill([
            'remote_method' => $this->remotePredictionMethod(),
            'remote_calculation_id' => $submission->calculationId,
            'remote_molecule_id' => $submission->moleculeId,
            'remote_status' => $submission->calculationStatus instanceof RemotePredictionStatus
                ? $submission->calculationStatus->value
                : (string) $submission->calculationStatus,
            'remote_submitted_at' => $this->remote_submitted_at ?? now(),
            'remote_last_status_at' => now(),
            'remote_error_message' => null,
            'state' => self::STATE_RUNNING,
            'step' => self::STEP_PENDING,
        ])->save();

        $moleculeStatus = $submission->moleculeStatus instanceof RemotePredictionStatus
            ? $submission->moleculeStatus->value
            : (string) $submission->moleculeStatus;

        $this->predictionStructure?->forceFill([
            'remote_molecule_status' => $moleculeStatus ?: null,
        ])->save();

        return $submission;
    }

    public function remotePredictionStatus(
        int $eventsLimit = 30,
        ?RemotePredictionClient $client = null,
    ): RemotePredictionJobSnapshot {
        $client = $this->remotePredictionClient($client);
        $membraneKey = $this->remotePredictionMembraneKey(client: $client);

        return $client->jobStatus(
            $this->remotePredictionSmiles(),
            $eventsLimit,
            $membraneKey,
            (float) $this->temperature,
        );
    }

    public function requeueRemotePrediction(
        RemotePredictionStep $step,
        bool $force = false,
        ?RemotePredictionClient $client = null,
    ): RemotePredictionJobSnapshot {
        $client = $this->remotePredictionClient($client);

        return $client->requeueJob(
            $this->remotePredictionSmiles(),
            $this->remotePredictionMembraneKey(client: $client),
            (float) $this->temperature,
            $step,
            $force,
        );
    }

    public function remotePredictionCalculation(
        ?RemotePredictionClient $client = null,
    ): ?RemotePredictionCalculation {
        $client = $this->remotePredictionClient($client);
        $membraneKey = $this->remotePredictionMembraneKey(client: $client);
        $snapshot = $client->jobStatus(
            $this->remotePredictionSmiles(),
            0,
            $membraneKey,
            (float) $this->temperature,
        );

        if (filled($this->remote_calculation_id)) {
            return $snapshot->calculationById((string) $this->remote_calculation_id)
                ?? $snapshot->calculationFor($membraneKey, (float) $this->temperature);
        }

        return $snapshot->calculationFor($membraneKey, (float) $this->temperature);
    }

    public function refreshRemotePredictionState(
        int $eventsLimit = 100,
        ?RemotePredictionClient $client = null,
    ): RemotePredictionJobSnapshot {
        $snapshot = $this->remotePredictionStatus($eventsLimit, $client);
        $calculation = filled($this->remote_calculation_id)
            ? $snapshot->calculationById((string) $this->remote_calculation_id)
            : null;
        $calculation ??= $snapshot->calculationFor(
            $this->remotePredictionMembraneKey(client: $this->remotePredictionClient($client)),
            (float) $this->temperature,
        );

        $calcStatus = $this->remoteStatusValue($calculation?->status);
        $cosmoPhase = in_array($calcStatus, [
            RemotePredictionStatus::QUEUED->value,
            RemotePredictionStatus::RUNNING->value,
            RemotePredictionStatus::WAITING_FOR_SCRIPT->value,
            RemotePredictionStatus::COMPLETED->value,
            RemotePredictionStatus::FAILED->value,
        ], true);
        $status = $cosmoPhase ? $calcStatus : $this->remoteStatusValue($snapshot->status);
        $currentStep = $this->remoteStepValue($this->resolveCurrentStep($snapshot, $calculation));
        $heartbeatAt = $this->heartbeatForSnapshot($snapshot, $calculation, $currentStep);

        $this->forceFill([
            'remote_method' => $this->remote_method ?: $this->remotePredictionMethod(),
            'remote_calculation_id' => $calculation?->id ?: $this->remote_calculation_id,
            'remote_molecule_id' => $calculation?->moleculeId ?: ($snapshot->id ?: $this->remote_molecule_id),
            'remote_status' => $status,
            'remote_current_step' => $currentStep,
            'remote_heartbeat_at' => $heartbeatAt,
            'remote_last_status_at' => now(),
            'remote_finished_at' => $this->remoteFinishedAt($status, $calculation?->finishedAt),
            'remote_error_message' => $status === RemotePredictionStatus::FAILED->value
                ? $calculation?->message
                : null,
            'logs' => $snapshot->toArray()['events'] ?? [],
            'state' => $this->stateForRemoteStatus($status),
            'step' => $this->stepForRemoteStep($status, $currentStep),
        ])->save();

        $moleculeStatus = $this->remoteStatusValue($snapshot->status);
        $this->predictionStructure?->forceFill([
            'remote_molecule_status' => $moleculeStatus ?: null,
        ])->save();

        return $snapshot;
    }

    public function downloadRemotePredictionResult(
        ?RemotePredictionClient $client = null,
    ): RemotePredictionFile {
        return $this->remotePredictionClient($client)
            ->downloadCalculation($this->remotePredictionCalculationId($client));
    }

    public function downloadRemotePredictionArtifact(
        RemotePredictionArtifact $artifact,
        ?RemotePredictionClient $client = null,
    ): RemotePredictionFile {
        return $this->remotePredictionClient($client)
            ->downloadArtifact($this->remotePredictionCalculationId($client), $artifact);
    }

    public function downloadRemotePredictionConformers(
        ?RemotePredictionClient $client = null,
    ): RemotePredictionFile {
        return $this->downloadRemotePredictionArtifact(RemotePredictionArtifact::CONFORMERS, $client);
    }

    public function downloadRemotePredictionSdf(
        ?RemotePredictionClient $client = null,
    ): RemotePredictionFile {
        return $this->downloadRemotePredictionArtifact(RemotePredictionArtifact::SDF, $client);
    }

    public function downloadRemotePredictionCosmoArchive(
        ?RemotePredictionClient $client = null,
    ): RemotePredictionFile {
        return $this->downloadRemotePredictionArtifact(RemotePredictionArtifact::COSMO, $client);
    }

    private function resolveCurrentStep(
        RemotePredictionJobSnapshot $snapshot,
        ?RemotePredictionCalculation $calculation,
    ): RemotePredictionStep|string|null {
        // Calculation is actively queued or running for COSMO — optimization phase is complete
        if ($calculation !== null && in_array(
            $this->remoteStatusValue($calculation->status),
            [
                RemotePredictionStatus::QUEUED->value,
                RemotePredictionStatus::RUNNING->value,
                RemotePredictionStatus::WAITING_FOR_SCRIPT->value,
            ],
            true,
        )) {
            return RemotePredictionStep::COSMO;
        }

        // Look for a running step inside conformers (optimization phase)
        foreach ($snapshot->conformers as $conformer) {
            $running = $conformer->steps->first(
                fn ($step): bool => $this->remoteStatusValue($step->status) === RemotePredictionStatus::RUNNING->value,
            );
            if ($running !== null) {
                return $running->step;
            }
        }

        // Look for a running step at molecule level (rdkit, conformers)
        $topRunning = $snapshot->steps->first(
            fn ($step): bool => $this->remoteStatusValue($step->status) === RemotePredictionStatus::RUNNING->value,
        );

        return $topRunning?->step ?? $snapshot->currentStep;
    }

    private function remotePredictionCalculationId(?RemotePredictionClient $client): string
    {
        $calculation = $this->remotePredictionCalculation($client);

        if (! $calculation) {
            throw new RuntimeException("RemotePrediction calculation for prediction {$this->getKey()} was not found.");
        }

        return $calculation->id;
    }

    private function remoteStatusValue(RemotePredictionStatus|string|null $status): ?string
    {
        if ($status instanceof RemotePredictionStatus) {
            return $status->value;
        }

        return filled($status) ? (string) $status : null;
    }

    private function remoteStepValue(RemotePredictionStep|string|null $step): ?string
    {
        if ($step instanceof RemotePredictionStep) {
            return $step->value;
        }

        return filled($step) ? (string) $step : null;
    }

    private function heartbeatForSnapshot(
        RemotePredictionJobSnapshot $snapshot,
        ?RemotePredictionCalculation $calculation,
        ?string $currentStep,
    ): ?CarbonInterface {
        if ($currentStep !== null) {
            $step = $snapshot->steps->first(
                fn ($step): bool => $this->remoteStepValue($step->step) === $currentStep,
            );

            if ($step?->heartbeatAt) {
                return $step->heartbeatAt;
            }
        }

        $runningStep = $snapshot->steps->first(
            fn ($step): bool => $this->remoteStatusValue($step->status) === RemotePredictionStatus::RUNNING->value,
        );

        return $runningStep?->heartbeatAt ?? $calculation?->heartbeatAt;
    }

    private function remoteFinishedAt(?string $status, ?CarbonInterface $finishedAt): ?CarbonInterface
    {
        if (in_array($status, [
            RemotePredictionStatus::COMPLETED->value,
            RemotePredictionStatus::FAILED->value,
        ], true)) {
            return $finishedAt ?? $this->remote_finished_at ?? now();
        }

        return null;
    }

    private function stateForRemoteStatus(?string $status): int
    {
        return match ($status) {
            RemotePredictionStatus::FAILED->value => self::STATE_ERROR,
            RemotePredictionStatus::COMPLETED->value => self::STATE_RUNNING,
            default => self::STATE_RUNNING,
        };
    }

    private function stepForRemoteStep(?string $status, ?string $currentStep): int
    {
        if ($status === RemotePredictionStatus::COMPLETED->value) {
            return self::STEP_RESULT_DOWNLOAD;
        }

        if ($status === RemotePredictionStatus::FAILED->value) {
            return max((int) $this->step, self::STEP_PENDING);
        }

        return match ($currentStep) {
            RemotePredictionStep::RDKIT->value => self::STEP_SDF_READY,
            RemotePredictionStep::CONFORMERS->value,
            RemotePredictionStep::OPTIMIZATION_ORCA->value,
            RemotePredictionStep::OPTIMIZATION_TURBOMOLE->value => self::STEP_OPTIMIZATION,
            RemotePredictionStep::COSMO->value => self::STEP_COSMO,
            default => max((int) $this->step, self::STEP_PENDING),
        };
    }

    private function remotePredictionMembraneKey(
        ?string $preferredKey = null,
        ?RemotePredictionClient $client = null,
    ): string {
        $membrane = $this->predictionMembrane;

        if (! $membrane) {
            throw new RuntimeException("Prediction {$this->getKey()} has no membrane.");
        }

        return $membrane->ensureRemotePredictionKey($preferredKey, $this->remotePredictionClient($client));
    }

    private function remotePredictionSmiles(): string
    {
        $structure = $this->predictionStructure;

        if (! $structure) {
            throw new RuntimeException("Prediction {$this->getKey()} has no structure.");
        }

        return $structure->remotePredictionSmiles();
    }

    private function remotePredictionClient(?RemotePredictionClient $client): RemotePredictionClient
    {
        return $client ?? app(RemotePredictionClient::class);
    }
}
