<?php

namespace App\Console\Commands\Cron;

use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Models\PredictionDataset;

class SendPredictionProgressNotifications extends Command
{
    protected $signature = 'cron:predictions-daily-progress';

    protected $description = 'Send daily progress emails for all in-progress prediction datasets.';

    public function handle(NotificationService $notificationService): int
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $sent = 0;

        PredictionDataset::query()
            ->whereNotNull('user_id')
            ->whereNull('finished_notification_sent_at')
            ->get()
            ->each(function (PredictionDataset $dataset) use ($notificationService, $frontendUrl, &$sent): void {
                $stats = $dataset->calculateProgressStats();
                $state = $stats['state'];

                // Only send for actively running/pending datasets
                if (! in_array($state, [PredictionDataset::STATE_RUNNING, PredictionDataset::STATE_PENDING], true)) {
                    return;
                }

                $user = User::find($dataset->user_id);
                if (! $user) {
                    return;
                }

                $membrane = $dataset->predictionMembrane?->name ?? 'N/A';
                $method = Prediction::$enum_methods[$dataset->method_type] ?? $dataset->method_type;
                $s = $stats['stats'];

                $notificationService->send($user, NotificationTemplate::KEY_PREDICTION_JOB_DAILY_PROGRESS, [
                    'comment' => $dataset->comment ?: "Dataset #{$dataset->id}",
                    'total' => $s['total'],
                    'done' => $s['done'],
                    'running' => $s['running'],
                    'pending' => $s['pending'],
                    'failed' => $s['failed'],
                    'membrane' => $membrane,
                    'method' => $method,
                    'dataset_url' => "{$frontendUrl}/lab/running-predictions?token={$dataset->token}",
                ]);

                $sent++;
            });

        $this->info("Sent {$sent} daily prediction progress notification(s).");

        return Command::SUCCESS;
    }
}
