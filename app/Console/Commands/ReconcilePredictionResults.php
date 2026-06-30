<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\PredictionWorkers\Models\Prediction;

class ReconcilePredictionResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:reconcile-results';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies existing predictions against their stored result files and requeues anything unfinished. Does not touch predictions owned by the live remote pipeline.';

    public function handle(): int
    {
        $base = Prediction::query()->whereNull('remote_calculation_id');

        $total = $base->count();
        $this->warn("Total {$total} records will be checked.");

        $processed = 0;
        $finished = 0;
        $failed = 0;
        $queued = 0;
        $unchanged = 0;

        Prediction::query()
            ->whereNull('remote_calculation_id')
            ->with('predictionResult.file')
            ->orderBy('id')
            ->chunkById(500, function ($predictions) use (&$processed, &$finished, &$failed, &$queued, &$unchanged, $total) {
                foreach ($predictions as $p) {
                    $processed++;

                    $hasVerifiedResult = $p->result_id && $p->predictionResult?->file?->exists();

                    if ($hasVerifiedResult) {
                        if ($p->state !== Prediction::STATE_FINISHED || $p->step !== Prediction::STEP_RESULT_DB_STORE) {
                            $p->state = Prediction::STATE_FINISHED;
                            $p->step = Prediction::STEP_RESULT_DB_STORE;
                            $p->save();

                            $this->line("[{$processed}/{$total}] prediction #{$p->id}: [FINISHED] verified result file exists.");
                        } else {
                            $unchanged++;
                        }

                        $finished++;

                        continue;
                    }

                    // Already known to have failed - nothing here can confirm or deny that
                    // without the legacy database, so leave it as is.
                    if ($p->state === Prediction::STATE_ERROR) {
                        $unchanged++;
                        $failed++;

                        continue;
                    }

                    if ($p->state !== Prediction::STATE_PREPARED || $p->step !== Prediction::STEP_PENDING) {
                        $p->state = Prediction::STATE_PREPARED;
                        $p->step = Prediction::STEP_PENDING;
                        $p->save();

                        $this->line("[{$processed}/{$total}] prediction #{$p->id}: [PENDING] no verified result, requeued.");
                    } else {
                        $unchanged++;
                    }

                    $queued++;
                }
            });

        $this->info("Done. Finished: {$finished}, failed (left as is): {$failed}, queued: {$queued}, unchanged: {$unchanged}.");

        return Command::SUCCESS;
    }
}
