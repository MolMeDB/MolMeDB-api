<?php

namespace App\Console\Commands\Cron;

use App\Models\NotificationTemplate;
use App\Services\PredictionAdminNotifier;
use App\Services\SystemActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\PredictionWorkers\Models\Prediction;

class SendPredictionAdminStatsNotification extends Command
{
    protected $signature = 'cron:predictions-admin-stats
        {period : One of: day, week, month, year}';

    protected $description = 'Send admin statistics report for predictions over the previous calendar period.';

    public function handle(
        PredictionAdminNotifier $notifier,
        SystemActivityLogger $activityLogger,
    ): int {
        $period = $this->argument('period');

        [$label, $from, $to] = match ($period) {
            'day' => $this->previousDay(),
            'week' => $this->previousWeek(),
            'month' => $this->previousMonth(),
            'year' => $this->previousYear(),
            default => [null, null, null],
        };

        if ($label === null) {
            $this->error('Invalid period. Use one of: day, week, month, year.');

            return Command::FAILURE;
        }

        $failedStates = Prediction::failedStates();
        $base = Prediction::query();

        $added = (clone $base)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $optimized = (clone $base)
            ->where('step', '>=', Prediction::STEP_COSMO)
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        $cosmoDone = (clone $base)
            ->whereNotNull('result_id')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        $failed = (clone $base)
            ->whereIn('state', $failedStates)
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        $running = (clone $base)
            ->where('state', Prediction::STATE_RUNNING)
            ->whereNull('remote_paused_at')
            ->count();
        $totalAll = (clone $base)->count();

        $notifier->notify(NotificationTemplate::KEY_PREDICTION_ADMIN_STATS_REPORT, [
            'period_label' => $label,
            'period_from' => $from->toDateString(),
            'period_to' => $to->toDateString(),
            'added' => $added,
            'optimized' => $optimized,
            'cosmo_done' => $cosmoDone,
            'failed' => $failed,
            'running' => $running,
            'total_all' => $totalAll,
        ]);

        $this->info("Sent admin stats report ({$label}): added={$added} optimized={$optimized} cosmo_done={$cosmoDone} failed={$failed}.");

        $activityLogger->log(
            event: 'prediction_admin_stats_sent',
            description: "Prediction admin statistics report sent for {$label}.",
            properties: [
                'period' => $period,
                'period_label' => $label,
                'added' => $added,
                'optimized' => $optimized,
                'cosmo_done' => $cosmoDone,
                'failed' => $failed,
                'running' => $running,
                'total' => $totalAll,
            ],
        );

        return Command::SUCCESS;
    }

    /** @return array{string, Carbon, Carbon} */
    private function previousDay(): array
    {
        $day = Carbon::yesterday();

        return [
            $day->format('l, d.m.Y'),
            $day->copy()->startOfDay(),
            $day->copy()->endOfDay(),
        ];
    }

    /** @return array{string, Carbon, Carbon} */
    private function previousWeek(): array
    {
        $monday = Carbon::now()->subWeek()->startOfWeek();
        $sunday = $monday->copy()->endOfWeek();

        return [
            $monday->format('d.m.Y').' – '.$sunday->format('d.m.Y'),
            $monday->startOfDay(),
            $sunday->endOfDay(),
        ];
    }

    /** @return array{string, Carbon, Carbon} */
    private function previousMonth(): array
    {
        $start = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [
            $start->format('F Y'),
            $start->startOfDay(),
            $end->endOfDay(),
        ];
    }

    /** @return array{string, Carbon, Carbon} */
    private function previousYear(): array
    {
        $start = Carbon::now()->subYear()->startOfYear();
        $end = $start->copy()->endOfYear();

        return [
            $start->format('Y'),
            $start->startOfDay(),
            $end->endOfDay(),
        ];
    }
}
