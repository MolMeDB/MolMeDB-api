<?php

namespace Modules\PredictionWorkers\Models;

use App\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;

class PredictionDataset extends PredictionBaseModel
{
    use Filterable;

    protected $attributes = [
        'priority' => Prediction::PRIORITY_MEDIUM,
    ];

    const STATE_EMPTY = 0;

    const STATE_PENDING = 1;

    const STATE_RUNNING = 2;

    const STATE_FINISHED = 3;

    const STATE_FINISHED_WITH_ERRORS = 4;

    const STATE_FAILED = 5;

    private const PROGRESS_STATS_CACHE_FRESH_SECONDS = 300;

    private const PROGRESS_STATS_CACHE_STALE_SECONDS = 600;

    public static $enum_states = [
        self::STATE_EMPTY => 'Empty',
        self::STATE_PENDING => 'Pending',
        self::STATE_RUNNING => 'In progress',
        self::STATE_FINISHED => 'Finished',
        self::STATE_FINISHED_WITH_ERRORS => 'Finished with errors',
        self::STATE_FAILED => 'Failed',
    ];

    protected $connection = 'predictions';

    protected $table = 'datasets';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'finished_notification_sent_at' => 'datetime',
    ];

    /**
     * @return array{
     *     state: int,
     *     enum_state: string,
     *     stats: array{pending: int, running: int, done: int, failed: int, total: int},
     *     overall_stats: array{progress_percent: int, completed_percent: int, steps_done: int, steps_total: int}
     * }
     */
    public function cachedProgressStats(): array
    {
        return $this->progressStatsCache()->flexible(
            $this->progressStatsCacheKey(),
            [
                self::PROGRESS_STATS_CACHE_FRESH_SECONDS,
                self::PROGRESS_STATS_CACHE_STALE_SECONDS,
            ],
            fn () => $this->calculateProgressStats(),
        );
    }

    public function forgetProgressStatsCache(): bool
    {
        return $this->progressStatsCache()->forget($this->progressStatsCacheKey());
    }

    /**
     * @return array{
     *     state: int,
     *     enum_state: string,
     *     stats: array{pending: int, running: int, done: int, failed: int, total: int},
     *     overall_stats: array{progress_percent: int, completed_percent: int, steps_done: int, steps_total: int}
     * }
     */
    public function calculateProgressStats(): array
    {
        $finalStep = Prediction::finalStep();
        $failedStates = Prediction::failedStates();

        $stats = Prediction::query()
            ->join('prediction_has_datasets', 'predictions.id', '=', 'prediction_has_datasets.prediction_id')
            ->where('prediction_has_datasets.dataset_id', $this->getKey())
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN predictions.state IN (?, ?, ?) THEN 1 ELSE 0 END), 0) as failed',
                $failedStates,
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN predictions.state NOT IN (?, ?, ?) AND (predictions.result_id IS NOT NULL OR predictions.step >= ?) THEN 1 ELSE 0 END), 0) as done',
                [...$failedStates, $finalStep],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN predictions.state NOT IN (?, ?, ?) AND predictions.result_id IS NULL AND predictions.step < ? AND (predictions.state = ? OR predictions.step > ?) THEN 1 ELSE 0 END), 0) as running',
                [...$failedStates, $finalStep, Prediction::STATE_RUNNING, Prediction::STEP_PENDING],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN predictions.state NOT IN (?, ?, ?) AND predictions.state != ? AND predictions.result_id IS NULL AND (predictions.step IS NULL OR predictions.step <= ?) THEN 1 ELSE 0 END), 0) as pending',
                [...$failedStates, Prediction::STATE_RUNNING, Prediction::STEP_PENDING],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN predictions.result_id IS NOT NULL OR predictions.step >= ? THEN ? WHEN predictions.step IS NULL OR predictions.step < 0 THEN 0 ELSE predictions.step END), 0) as progress_steps',
                [$finalStep, $finalStep],
            )
            ->first();

        $total = (int) $stats->total;
        $pending = (int) $stats->pending;
        $running = (int) $stats->running;
        $done = (int) $stats->done;
        $failed = (int) $stats->failed;
        $progressSteps = (int) $stats->progress_steps;
        $stepsTotal = $total * $finalStep;
        $state = self::resolveState($pending, $running, $done, $failed, $total);

        return [
            'state' => $state,
            'enum_state' => self::enumState($state),
            'stats' => [
                'pending' => $pending,
                'running' => $running,
                'done' => $done,
                'failed' => $failed,
                'total' => $total,
            ],
            'overall_stats' => [
                'progress_percent' => $stepsTotal > 0 ? (int) round(($progressSteps / $stepsTotal) * 100) : 0,
                'completed_percent' => $total > 0 ? (int) round((($done + $failed) / $total) * 100) : 0,
                'steps_done' => $progressSteps,
                'steps_total' => $stepsTotal,
            ],
        ];
    }

    public function predictions(): BelongsToMany
    {
        return $this->belongsToMany(Prediction::class, 'prediction_has_datasets', 'dataset_id', 'prediction_id')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function method($method): string
    {
        return Prediction::$enum_methods[$method];
    }

    public static function enum_priority($priority): string
    {
        return Prediction::$enum_priorities[$priority];
    }

    public static function resolveState(int $pending, int $running, int $done, int $failed, int $total): int
    {
        if ($total === 0) {
            return self::STATE_EMPTY;
        }

        if ($failed === $total) {
            return self::STATE_FAILED;
        }

        if (($done + $failed) === $total) {
            return $failed > 0
                ? self::STATE_FINISHED_WITH_ERRORS
                : self::STATE_FINISHED;
        }

        if ($running > 0 || $done > 0 || $failed > 0) {
            return self::STATE_RUNNING;
        }

        if ($pending > 0) {
            return self::STATE_PENDING;
        }

        return self::STATE_EMPTY;
    }

    public static function enumState(int $state): string
    {
        return self::$enum_states[$state] ?? 'N/A';
    }

    private function progressStatsCache(): Repository
    {
        return Cache::store(config('cache.prediction_stats_store', 'redis'));
    }

    private function progressStatsCacheKey(): string
    {
        return "prediction-datasets:{$this->getKey()}:progress-stats:v2";
    }

    public function predictionMembrane(): BelongsTo
    {
        return $this->belongsTo(PredictionMembrane::class, 'membrane_id');
    }

    public function membrane(): BelongsTo
    {
        return $this->predictionMembrane->membrane();
    }

    public function predictionStructures(): HasManyThrough
    {
        return $this->hasManyThrough(
            PredictionStructure::class,
            Prediction::class,
            'id',            // Foreign key on predictions
            'id',            // Foreign key on prediction_structures
            'id',            // Local key on current model
            'structure_id'   // Local key on predictions
        );
    }
}
