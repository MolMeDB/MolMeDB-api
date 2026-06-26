<?php

namespace App\Console\Commands\Cron;

use Illuminate\Console\Command;
use Modules\PredictionWorkers\Enums\RemotePredictionStatus;
use Modules\PredictionWorkers\Exceptions\RemotePredictionDisabledException;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Models\PredictionStat;
use Modules\PredictionWorkers\Services\RemotePrediction\RemotePredictionClient;
use Throwable;

class RunPredictionsWorker extends Command
{
    protected $signature = 'cron:predictions-worker
        {--max-status= : Maximum running predictions to refresh}
        {--max-submit= : Maximum new predictions to submit}
        {--events-limit= : Maximum remote events stored per prediction}
        {--skip-stats : Do not fetch remote server statistics}
        {--skip-status : Do not refresh running prediction statuses}
        {--skip-submit : Do not submit prepared predictions}';

    protected $description = 'Synchronize remote prediction statistics, running statuses and queued submissions.';

    public function handle(RemotePredictionClient $client): int
    {
        if (! $client->isEnabled()) {
            $this->warn('Remote prediction service is disabled.');

            return Command::SUCCESS;
        }

        try {
            $client->ensureValidToken();
        } catch (Throwable $throwable) {
            $this->error('Failed to ensure valid token: '.$throwable->getMessage());

            return Command::FAILURE;
        }

        $results = [
            'stats' => $this->option('skip-stats') ? null : $this->refreshStatistics($client),
            'statuses' => $this->option('skip-status') ? null : $this->refreshStatuses($client),
            'submissions' => $this->option('skip-submit') ? null : $this->submitPreparedPredictions($client),
        ];

        $this->info(sprintf(
            'Remote prediction worker finished. Stats: %s, statuses: %s, submissions: %s.',
            $results['stats'] === null ? 'skipped' : 'stored',
            $results['statuses'] === null ? 'skipped' : (string) $results['statuses'],
            $results['submissions'] === null ? 'skipped' : (string) $results['submissions'],
        ));

        return Command::SUCCESS;
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
            $this->error('Remote statistics refresh failed: '.$throwable->getMessage());

            return null;
        }
    }

    private function refreshStatuses(RemotePredictionClient $client): int
    {
        $limit = $this->integerOption(
            'max-status',
            (int) config('prediction-workers.remote.worker.max_status_updates', 20),
        );
        $eventsLimit = $this->integerOption(
            'events-limit',
            (int) config('prediction-workers.remote.worker.events_limit', 100),
        );
        $statusIntervalSeconds = max(
            1,
            (int) config('prediction-workers.remote.worker.status_interval_seconds', 300),
        );
        $updated = 0;

        Prediction::query()
            ->whereNotNull('remote_calculation_id')
            ->whereNull('result_id')
            ->whereNotIn('state', Prediction::failedStates())
            ->where(function ($query): void {
                $query
                    ->whereNull('remote_status')
                    ->orWhereNotIn('remote_status', [
                        RemotePredictionStatus::COMPLETED->value,
                        RemotePredictionStatus::FAILED->value,
                    ]);
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
            ->each(function (Prediction $prediction) use ($client, $eventsLimit, &$updated): void {
                try {
                    $prediction->refreshRemotePredictionState($eventsLimit, $client);
                    $updated++;
                } catch (Throwable $throwable) {
                    $prediction->forceFill([
                        'remote_last_status_at' => now(),
                        'remote_error_message' => $throwable->getMessage(),
                    ])->save();

                    $this->warn("Prediction {$prediction->getKey()} status refresh failed: {$throwable->getMessage()}");
                }
            });

        return $updated;
    }

    private function submitPreparedPredictions(RemotePredictionClient $client): int
    {
        $limit = $this->integerOption(
            'max-submit',
            (int) config('prediction-workers.remote.worker.max_submissions', 5),
        );
        $submitted = 0;

        Prediction::query()
            ->whereNull('remote_calculation_id')
            ->whereNull('result_id')
            ->where('state', Prediction::STATE_PREPARED)
            ->whereIn('method_type', array_keys(Prediction::remotePredictionMethodOptions()))
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Prediction $prediction) use ($client, &$submitted): void {
                try {
                    $prediction->submitAndStoreRemotePrediction(client: $client);
                    $submitted++;
                } catch (Throwable $throwable) {
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

    private function integerOption(string $name, int $default): int
    {
        $value = $this->option($name);

        return max(0, $value === null ? $default : (int) $value);
    }
}
