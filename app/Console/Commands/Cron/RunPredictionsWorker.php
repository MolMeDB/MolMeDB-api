<?php

namespace App\Console\Commands\Cron;

use App\Jobs\CheckPredictionStatus;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PredictionSubmissionStructureValidator;
use App\Services\SystemActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\PredictionWorkers\Enums\RemotePredictionStatus;
use Modules\PredictionWorkers\Exceptions\RemotePredictionDisabledException;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Models\PredictionDataset;
use Modules\PredictionWorkers\Models\PredictionStat;
use Modules\PredictionWorkers\Services\RemotePrediction\RemotePredictionClient;
use Throwable;

class RunPredictionsWorker extends Command
{
    private int $statisticsErrors = 0;

    private int $downloadErrors = 0;

    private int $submissionErrors = 0;

    private int $rejectedStructures = 0;

    protected $signature = 'cron:predictions-worker
        {--max-results= : Maximum completed prediction results to download}
        {--max-submit= : Maximum new predictions to submit}
        {--skip-stats : Do not fetch remote server statistics}
        {--skip-results : Do not download completed prediction results}
        {--skip-submit : Do not submit prepared predictions}
        {--skip-dispatch : Do not dispatch queue jobs for running predictions}';

    protected $description = 'Fetch remote statistics, submit prepared predictions, download completed results, and dispatch queue jobs for running status checks.';

    public function handle(
        RemotePredictionClient $client,
        PredictionSubmissionStructureValidator $structureValidator,
        SystemActivityLogger $activityLogger,
    ): int {
        if (! $client->isEnabled()) {
            $this->warn('Remote prediction service is disabled.');

            return Command::SUCCESS;
        }

        $lock = Cache::lock('remote-prediction:worker', 600);

        if (! $lock->get()) {
            $this->warn('Another remote prediction worker is already running.');

            return Command::SUCCESS;
        }

        try {
            try {
                $client->ensureValidToken();
            } catch (Throwable $throwable) {
                $this->error('Failed to ensure valid token: '.$throwable->getMessage());

                $activityLogger->logThrottled(
                    event: 'prediction_worker_authentication_failed',
                    description: 'Remote prediction worker could not authenticate.',
                    properties: [
                        'exception' => $throwable::class,
                        'error' => $throwable->getMessage(),
                    ],
                    throttleKey: 'remote-prediction-authentication',
                );

                return Command::FAILURE;
            }

            $results = [
                'stats' => $this->option('skip-stats') ? null : $this->refreshStatistics($client),
                'downloads' => $this->option('skip-results') ? null : $this->downloadCompletedResults($client),
                'submissions' => $this->option('skip-submit')
                    ? null
                    : $this->submitPreparedPredictions($client, $structureValidator),
                'dispatched' => $this->option('skip-dispatch') ? null : $this->dispatchStatusChecks(),
            ];

            $this->info(sprintf(
                'Remote prediction worker finished. Stats: %s, submissions: %s, downloads: %s, dispatched: %s.',
                $results['stats'] === null ? 'skipped' : 'stored',
                $results['submissions'] === null ? 'skipped' : (string) $results['submissions'],
                $results['downloads'] === null ? 'skipped' : (string) $results['downloads'],
                $results['dispatched'] === null ? 'skipped' : (string) $results['dispatched'],
            ));

            $errorCount = $this->statisticsErrors
                + $this->downloadErrors
                + $this->submissionErrors
                + $this->rejectedStructures;
            $processedCount = ($results['submissions'] ?? 0) + ($results['downloads'] ?? 0);

            if ($processedCount > 0 || $errorCount > 0) {
                $description = sprintf(
                    'Prediction worker submitted %d and downloaded %d prediction(s); encountered %d error(s).',
                    $results['submissions'] ?? 0,
                    $results['downloads'] ?? 0,
                    $errorCount,
                );
                $properties = [
                    'statistics_stored' => $results['stats'] instanceof PredictionStat,
                    'submissions' => $results['submissions'],
                    'downloads' => $results['downloads'],
                    'dispatched' => $results['dispatched'],
                    'statistics_errors' => $this->statisticsErrors,
                    'download_errors' => $this->downloadErrors,
                    'submission_errors' => $this->submissionErrors,
                    'rejected_structures' => $this->rejectedStructures,
                ];

                if ($errorCount > 0) {
                    $activityLogger->logThrottled(
                        event: 'prediction_worker_completed_with_errors',
                        description: $description,
                        properties: $properties,
                        throttleKey: 'prediction-worker-errors',
                    );
                } else {
                    $activityLogger->log(
                        event: 'prediction_worker_completed',
                        description: $description,
                        properties: $properties,
                    );
                }
            }

            return Command::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function refreshStatistics(RemotePredictionClient $client): ?PredictionStat
    {
        try {
            return PredictionStat::storeDailySnapshot(
                $client->baseUrl(),
                now(),
                $client->pipelineStatistics()->toArray(),
            );
        } catch (RemotePredictionDisabledException $throwable) {
            throw $throwable;
        } catch (Throwable $throwable) {
            $this->statisticsErrors++;
            $this->error('Remote statistics refresh failed: '.$throwable->getMessage());

            return null;
        }
    }

    /**
     * Dispatch a CheckPredictionStatus job for every actively running prediction.
     * Unique queue jobs prevent duplicate checks for the same prediction.
     */
    private function dispatchStatusChecks(): int
    {
        $limit = max(1, (int) config('prediction-workers.remote.worker.max_active', 100));
        $statusIntervalSeconds = max(30, (int) config('prediction-workers.remote.worker.status_interval_seconds', 300));
        $dispatched = 0;

        Prediction::query()
            ->whereNotNull('remote_calculation_id')
            ->whereNull('result_id')
            ->whereNotIn('state', Prediction::failedStates())
            ->where(function ($query): void {
                $query
                    ->whereNull('remote_status')
                    ->orWhereIn('remote_status', Prediction::activeRemoteStatuses());
            })
            ->where(function ($query) use ($statusIntervalSeconds): void {
                $query
                    ->whereNull('remote_last_status_at')
                    ->orWhere('remote_last_status_at', '<=', now()->subSeconds($statusIntervalSeconds));
            })
            ->orderByDesc('priority')
            ->orderBy('remote_last_status_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Prediction $prediction) use (&$dispatched): void {
                CheckPredictionStatus::dispatch($prediction->getKey());
                $dispatched++;
            });

        return $dispatched;
    }

    private function downloadCompletedResults(RemotePredictionClient $client): int
    {
        $limit = $this->integerOption(
            'max-results',
            (int) config('prediction-workers.remote.worker.max_result_downloads', 5),
        );
        $downloaded = 0;
        $finishedDatasetIds = [];

        Prediction::query()
            ->whereNotNull('remote_calculation_id')
            ->whereNull('result_id')
            ->where('remote_status', RemotePredictionStatus::COMPLETED->value)
            ->whereNotIn('state', Prediction::failedStates())
            ->orderByDesc('priority')
            ->orderBy('remote_finished_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Prediction $prediction) use ($client, &$downloaded, &$finishedDatasetIds): void {
                try {
                    $prediction->storeRemotePredictionResult($client);
                    $downloaded++;

                    // Track which datasets may now be complete
                    foreach ($prediction->predictionDatasets()->pluck('datasets.id') as $datasetId) {
                        $finishedDatasetIds[(int) $datasetId] = true;
                    }
                } catch (Throwable $throwable) {
                    $this->downloadErrors++;
                    $prediction->forceFill([
                        'remote_last_status_at' => now(),
                        'remote_error_message' => $throwable->getMessage(),
                    ])->save();

                    $this->warn("Prediction {$prediction->getKey()} result download failed: {$throwable->getMessage()}");
                }
            });

        // Send job-finished notification for datasets where all predictions are now done
        if ($finishedDatasetIds) {
            $this->notifyFinishedDatasets(array_keys($finishedDatasetIds));
        }

        return $downloaded;
    }

    private function submitPreparedPredictions(
        RemotePredictionClient $client,
        PredictionSubmissionStructureValidator $structureValidator,
    ): int {
        $requestedLimit = $this->integerOption(
            'max-submit',
            (int) config('prediction-workers.remote.worker.max_submissions', 5),
        );
        $maxActive = max(1, (int) config('prediction-workers.remote.worker.max_active', 100));
        $active = Prediction::query()
            ->whereNotNull('remote_calculation_id')
            ->whereNull('result_id')
            ->whereNotIn('state', Prediction::failedStates())
            ->where(function ($query): void {
                $query
                    ->whereNull('remote_status')
                    ->orWhereIn('remote_status', Prediction::activeRemoteStatuses());
            })
            ->count();
        $limit = min($requestedLimit, max(0, $maxActive - $active));

        if ($limit === 0) {
            return 0;
        }

        $submitted = 0;

        Prediction::query()
            ->with('predictionStructure')
            ->whereNull('remote_calculation_id')
            ->whereNull('result_id')
            ->where('state', Prediction::STATE_PREPARED)
            ->whereIn('method_type', array_keys(Prediction::remotePredictionMethodOptions()))
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Prediction $prediction) use ($client, $structureValidator, &$submitted): void {
                try {
                    if (! $structureValidator->passes($prediction)) {
                        $this->rejectedStructures++;
                        $this->warn("Prediction {$prediction->getKey()} was not submitted: {$prediction->remote_error_message}");

                        return;
                    }

                    $prediction->submitAndStoreRemotePrediction(client: $client);
                    CheckPredictionStatus::dispatch($prediction->getKey());
                    $submitted++;
                } catch (Throwable $throwable) {
                    $this->submissionErrors++;
                    $prediction->forceFill([
                        'remote_method' => Prediction::hasRemotePredictionMethod((string) $prediction->method_type)
                            ? $prediction->remotePredictionMethod()
                            : null,
                        'remote_last_status_at' => now(),
                        'remote_error_message' => $throwable->getMessage(),
                    ])->save();

                    $this->warn("Prediction {$prediction->getKey()} submit failed: {$throwable->getMessage()}");
                }
            });

        return $submitted;
    }

    /**
     * @param  int[]  $datasetIds
     */
    private function notifyFinishedDatasets(array $datasetIds): void
    {
        $notificationService = app(NotificationService::class);
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        PredictionDataset::query()
            ->whereIn('id', $datasetIds)
            ->whereNotNull('user_id')
            ->whereNull('finished_notification_sent_at')
            ->get()
            ->each(function (PredictionDataset $dataset) use ($notificationService, $frontendUrl): void {
                $stats = $dataset->calculateProgressStats();
                $state = $stats['state'];

                // Only notify when all predictions are done (finished or failed)
                if (! in_array($state, [PredictionDataset::STATE_FINISHED, PredictionDataset::STATE_FINISHED_WITH_ERRORS, PredictionDataset::STATE_FAILED], true)) {
                    return;
                }

                $user = User::find($dataset->user_id);
                if (! $user) {
                    return;
                }

                $membrane = $dataset->predictionMembrane?->name ?? 'N/A';
                $method = Prediction::enumMethod($dataset->method_type);
                $s = $stats['stats'];

                $notificationService->send($user, NotificationTemplate::KEY_PREDICTION_JOB_FINISHED, [
                    'comment' => $dataset->comment ?: "Dataset #{$dataset->id}",
                    'total' => $s['total'],
                    'done' => $s['done'],
                    'failed' => $s['failed'],
                    'membrane' => $membrane,
                    'method' => $method,
                    'dataset_url' => "{$frontendUrl}/lab/running-predictions?token={$dataset->token}",
                ]);

                $dataset->forceFill(['finished_notification_sent_at' => now()])->save();
            });
    }

    private function integerOption(string $name, int $default): int
    {
        $value = $this->option($name);

        return max(0, $value === null ? $default : (int) $value);
    }
}
