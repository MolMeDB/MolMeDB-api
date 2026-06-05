<?php

use App\Console\Commands\Cron\RunDailyCommands;
use App\Console\Commands\ProcessFrontendUploads;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Modules\PredictionWorkers\Models\PredictionDataset;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('cron:init', function () {
    $this->comment("[Running all daily jobs]\n");
    Artisan::call(RunDailyCommands::class, [], $this->getOutput());
})->purpose('Runs non-destructive jobs to (re)initialize the system.');

Artisan::command('predictions:refresh-dataset-stats {--chunk=200}', function () {
    $chunkSize = max(1, (int) $this->option('chunk'));
    $refreshed = 0;

    PredictionDataset::query()
        ->select('id')
        ->orderBy('id')
        ->chunkById($chunkSize, function ($datasets) use (&$refreshed) {
            foreach ($datasets as $dataset) {
                $dataset->forgetProgressStatsCache();
                $dataset->cachedProgressStats();
                $refreshed++;
            }
        });

    $this->info("Refreshed {$refreshed} prediction dataset stats.");

    return Command::SUCCESS;
})->purpose('Refresh cached prediction dataset progress statistics.');

Schedule::command('predictions:refresh-dataset-stats')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command(ProcessFrontendUploads::class)
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command(RunDailyCommands::class)
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cron/daily/cron-daily.log'))
    ->before(function () {
        $logPath = storage_path('logs/cron/daily/cron-daily.log');
        File::ensureDirectoryExists(dirname($logPath));
        if (file_exists($logPath) && filesize($logPath) > 50 * 1024 * 1024) {
            file_put_contents($logPath, '[Log truncated on '.now()."]\n");
        }
    });
