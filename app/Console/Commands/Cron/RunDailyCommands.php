<?php

namespace App\Console\Commands\Cron;

use App\Models\Config;
use Exception;
use Illuminate\Console\Command;

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
            \App\Console\Commands\UpdateStatistics::class => [],
            \App\Console\Commands\UpdateExportFiles::class => [],
            \App\Console\Commands\CheckStructureInternalIdentifiers::class => [
                'startId' => Config::get('cron:daily:check_structure_identifier:start_id', 1)
            ]
        ];

        $this->comment("\n-----------------------");
        $this->comment('Running daily jobs at ' . now()->toDateTimeString());
        $this->comment(".......\n");

        foreach ($commands as $command => $params)
        {
            $this->info($command);
            $this->call($command, $params);
            $this->info("...done\n");
        }

        $this->comment("\n.......");
        $this->comment("Done\n-----------------------\n\n");

        return Command::SUCCESS;
    }
}