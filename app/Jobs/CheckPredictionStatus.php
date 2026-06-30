<?php

namespace App\Jobs;

use App\Services\SystemActivityLogger;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Modules\PredictionWorkers\Enums\RemotePredictionStatus;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Services\RemotePrediction\RemotePredictionClient;
use Throwable;

class CheckPredictionStatus implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;

    public int $timeout = 120;

    public int $uniqueFor = 600;

    public function __construct(public readonly int $predictionId)
    {
        $this->onQueue(config('prediction-workers.remote.worker.queue', 'predictions'));
    }

    public function uniqueId(): string
    {
        return 'prediction_status_'.$this->predictionId;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new RateLimited('remote-prediction-status'))->releaseAfter(10),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHour();
    }

    public function handle(RemotePredictionClient $client): void
    {
        $prediction = Prediction::find($this->predictionId);

        if (! $prediction || ! $this->isActivelyRunning($prediction)) {
            return;
        }

        try {
            $client->ensureValidToken();
            $eventsLimit = (int) config('prediction-workers.remote.worker.events_limit', 100);
            $prediction->refreshRemotePredictionState($eventsLimit, $client);
        } catch (Throwable $e) {
            $prediction->forceFill([
                'remote_last_status_at' => now(),
                'remote_error_message' => $e->getMessage(),
            ])->save();

            app(SystemActivityLogger::class)->logThrottled(
                event: 'prediction_status_check_failed',
                description: 'Remote prediction status checks are failing.',
                properties: [
                    'prediction_id' => $prediction->getKey(),
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                ],
                throttleKey: 'remote-prediction-status',
            );
        }
    }

    private function isActivelyRunning(Prediction $prediction): bool
    {
        if (in_array($prediction->state, Prediction::failedStates())) {
            return false;
        }

        if ($prediction->state === Prediction::STATE_FINISHED) {
            return false;
        }

        if (! $prediction->remote_calculation_id) {
            return false;
        }

        if (in_array($prediction->remote_status, [
            RemotePredictionStatus::COMPLETED->value,
            RemotePredictionStatus::FAILED->value,
        ], true)) {
            return false;
        }

        return true;
    }
}
