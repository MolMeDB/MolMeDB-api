<?php

namespace App\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class RunPredictionsMigrationsAfterDefaultMigrate
{
    public function handle(CommandFinished $event): void
    {
        if (! $this->shouldRun($event)) {
            return;
        }

        $defaultMigrationTable = (string) config('database.migrations.table');
        $predictionsMigrationTable = (string) config('database.migrations_predictions.table', 'predictions_migrations');

        Config::set('database.migrations.table', $predictionsMigrationTable);

        try {
            Artisan::call('migrate', $this->migrationOptions($event), $event->output);
        } finally {
            Config::set('database.migrations.table', $defaultMigrationTable);
        }
    }

    protected function shouldRun(CommandFinished $event): bool
    {
        if ($event->command !== 'migrate' || $event->exitCode !== 0) {
            return false;
        }

        return ! $event->input->hasParameterOption('--database')
            && ! $event->input->hasParameterOption('--path')
            && ! $event->input->hasParameterOption('--realpath');
    }

    /**
     * @return array<string, bool|string>
     */
    protected function migrationOptions(CommandFinished $event): array
    {
        return array_filter([
            '--database' => (string) config('database.default_predictions', 'predictions'),
            '--path' => base_path('modules/PredictionWorkers/database/migrations'),
            '--realpath' => true,
            '--force' => $event->input->hasParameterOption('--force'),
            '--step' => $event->input->hasParameterOption('--step'),
            '--pretend' => $event->input->hasParameterOption('--pretend'),
            '--isolated' => $event->input->hasParameterOption('--isolated'),
        ], static fn (bool|string $value): bool => $value !== false);
    }
}
