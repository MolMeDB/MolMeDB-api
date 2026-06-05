<?php

namespace App\Console\Commands\Cron;

use App\Console\Commands\CheckStructureInternalIdentifiers;
use App\Console\Commands\UpdateExportFiles;
use App\Console\Commands\UpdateStatistics;
use App\Models\Config;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class RunDailyCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs all daily cron jobs.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $commands = [
            [
                'name' => UpdateStatistics::class,
                'label' => 'Update statistics',
                'parameters' => [],
            ],
            [
                'name' => UpdateExportFiles::class,
                'label' => 'Update export files',
                'parameters' => [],
            ],
            [
                'name' => CheckStructureInternalIdentifiers::class,
                'label' => 'Check structure internal identifiers',
                'parameters' => [
                    // '--startId' => Config::get('cron:daily:check_structure_identifier:start_id', 1),
                    '--startId' => 497600,
                ],
            ],
        ];

        $startedAt = now();
        $results = [];

        $this->comment("\n-----------------------");
        $this->comment('Running daily jobs at '.$startedAt->toDateTimeString());
        $this->comment(".......\n");

        foreach ($commands as $command) {
            $this->info($command['label']);

            $output = new BufferedOutput;
            $jobStartedAt = now();

            try {
                $exitCode = Artisan::call($command['name'], $command['parameters'], $output);
                $results[] = [
                    'label' => $command['label'],
                    'command' => $command['name'],
                    'successful' => $exitCode === Command::SUCCESS,
                    'exit_code' => $exitCode,
                    'output' => trim($output->fetch()),
                    'error' => null,
                    'started_at' => $jobStartedAt,
                    'finished_at' => now(),
                ];
            } catch (Throwable $throwable) {
                $results[] = [
                    'label' => $command['label'],
                    'command' => $command['name'],
                    'successful' => false,
                    'exit_code' => Command::FAILURE,
                    'output' => trim($output->fetch()),
                    'error' => $throwable::class.': '.$throwable->getMessage(),
                    'started_at' => $jobStartedAt,
                    'finished_at' => now(),
                ];
            }

            $result = end($results);
            $this->{$result['successful'] ? 'info' : 'error'}(
                ($result['successful'] ? '[OK] ' : '[FAILED] ').$command['label']
            );
        }

        $this->comment("\n.......");
        $this->comment($this->summaryText($results, $startedAt));
        $this->comment("\nDone\n-----------------------\n\n");

        $this->sendSummaryEmail($results, $startedAt, now());

        return collect($results)->every(fn (array $result): bool => $result['successful'])
            ? Command::SUCCESS
            : Command::FAILURE;
    }

    private function sendSummaryEmail(array $results, CarbonInterface $startedAt, CarbonInterface $finishedAt): void
    {
        $recipients = array_filter(array_map(
            fn (string $recipient): string => trim($recipient),
            explode(';', Config::get('email:cron:failure', '') ?? '')
        ));

        if (empty($recipients)) {
            return;
        }

        $successful = collect($results)->every(fn (array $result): bool => $result['successful']);

        Mail::html(
            $this->summaryHtml($results, $startedAt, $finishedAt),
            function ($message) use ($recipients, $successful, $startedAt): void {
                $message
                    ->to($recipients)
                    ->subject(sprintf(
                        '[MolMeDB] Daily cron %s - %s',
                        $successful ? 'completed' : 'failed',
                        $startedAt->toDateString(),
                    ));
            }
        );
    }

    private function summaryText(array $results, CarbonInterface $startedAt): string
    {
        $lines = [
            'Daily cron summary',
            'Started at: '.$startedAt->toDateTimeString(),
            '',
        ];

        foreach ($results as $result) {
            $lines[] = sprintf(
                '%s %s (%ss)',
                $result['successful'] ? '[OK]' : '[FAILED]',
                $result['label'],
                $result['started_at']->diffInSeconds($result['finished_at']),
            );
        }

        return implode("\n", $lines);
    }

    private function summaryHtml(array $results, CarbonInterface $startedAt, CarbonInterface $finishedAt): string
    {
        $successful = collect($results)->every(fn (array $result): bool => $result['successful']);
        $statusColor = $successful ? '#15803d' : '#b91c1c';

        $jobsHtml = collect($results)
            ->map(function (array $result): string {
                $status = $result['successful'] ? '✓' : '✕';
                $statusColor = $result['successful'] ? '#15803d' : '#b91c1c';
                $duration = $result['started_at']->diffInSeconds($result['finished_at']);
                $detail = '';

                if (! $result['successful']) {
                    $details = array_filter([
                        $result['error'],
                        $result['output'],
                    ]);

                    $detail = '<pre style="margin:12px 0 0;padding:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;white-space:pre-wrap;color:#374151;">'
                        .e(implode("\n\n", $details))
                        .'</pre>';
                }

                return sprintf(
                    '<li style="padding:14px 0;border-bottom:1px solid #e5e7eb;">
                        <div style="display:flex;gap:10px;align-items:center;">
                            <strong style="color:%s;font-size:18px;">%s</strong>
                            <div>
                                <div style="font-weight:700;color:#111827;">%s</div>
                                <div style="color:#6b7280;font-size:13px;">%s seconds</div>
                            </div>
                        </div>
                        %s
                    </li>',
                    $statusColor,
                    $status,
                    e($result['label']),
                    $duration,
                    $detail,
                );
            })
            ->implode('');

        return sprintf(
            '<div style="font-family:Arial,sans-serif;line-height:1.5;color:#111827;">
                <h1 style="margin:0 0 8px;font-size:22px;color:%s;">Daily cron %s</h1>
                <p style="margin:0 0 18px;color:#4b5563;">
                    Started: %s<br>
                    Finished: %s<br>
                    Duration: %s seconds
                </p>
                <ul style="list-style:none;padding:0;margin:0;">%s</ul>
            </div>',
            $statusColor,
            $successful ? 'completed successfully' : 'finished with errors',
            e($startedAt->toDateTimeString()),
            e($finishedAt->toDateTimeString()),
            $startedAt->diffInSeconds($finishedAt),
            $jobsHtml,
        );
    }
}
