<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Models\PredictionDataset;
use Modules\PredictionWorkers\Models\PredictionMembrane;
use Modules\PredictionWorkers\Models\PredictionStructure;

class UpdatePredictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Goes through all structures and checks for internal identifiers.';

    /**
     * Legacy `run_cosmo.status` values (see MolMeDB/MolMeDB application/helpers/Run/Cosmo.php).
     * Note these do NOT line up with the new Prediction::STATE_* numbering (2 and 3 are swapped),
     * so they must be mapped explicitly rather than copied raw.
     */
    private const OLD_STATUS_ERROR = 3;

    private function normalizePriority($priority): int
    {
        $valid = [Prediction::PRIORITY_LOW, Prediction::PRIORITY_MEDIUM, Prediction::PRIORITY_HIGH];

        return in_array((int) $priority, $valid, true) ? (int) $priority : Prediction::PRIORITY_MEDIUM;
    }

    private function old_conformer_folder($id_fragment, $id_ion = null)
    {
        $group = intval($id_fragment / 10000);
        $group = $group * 10000;
        $group .= '-'.($group + 10000);

        if (! $id_ion) {
            return $group.'/'.$id_fragment.'/';
        }

        return $group.'/'.$id_fragment.'/'.$id_ion.'/';
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $old_db = DB::connection('molmedb_old');

        $q = $old_db->table('run_cosmo')
            ->orderBy('id');

        $total = $old_db->table('run_cosmo')->count();

        $this->warn('Total '.$total.' records will be processed.');

        $predictions = $q->cursor();

        $processed = 0;

        foreach ($predictions as $prediction) {
            $processed++;
            $this->line("[{$processed}/{$total}] legacy run_cosmo id {$prediction->id}");

            $ions = $old_db->table('fragments_ionized')
                ->where('id_fragment', $prediction->id_fragment)
                ->get();

            $structures = [];

            if (count($ions)) {
                foreach ($ions as $ion) {
                    // Make structure
                    $s = PredictionStructure::firstOrCreate([
                        // 'id' => $ion->id
                        'canonical_smiles' => $ion->smiles,
                    ], [
                        // 'id' => $ion->id,
                        'base_path' => $this->old_conformer_folder($prediction->id_fragment, $ion->id),
                    ]);

                    $structures[] = $s;

                    $s->total_conformers = $s->totalRemoteConformers();
                    $s->save();
                }
            } else {
                $fragment = $old_db->table('fragments')
                    ->where('id', $prediction->id_fragment)
                    ->first();

                if (! $fragment) {
                    continue;
                }

                // Make structure
                $s = PredictionStructure::firstOrCreate([
                    'canonical_smiles' => $fragment->smiles,
                ], [
                    'base_path' => $this->old_conformer_folder($prediction->id_fragment),
                ]);

                $structures[] = $s;
            }

            $membrane = PredictionMembrane::find($prediction->id_membrane);
            if (! $membrane) {
                $membrane_remote = $old_db->table('membranes')
                    ->where('id', $prediction->id_membrane)
                    ->first();

                $membrane = PredictionMembrane::firstOrCreate([
                    'id' => $membrane_remote->id,
                ], [
                    'id' => $membrane_remote->id,
                    'name' => $membrane_remote->name,
                    'abbreviation' => $membrane_remote->CAM,
                ]);
            }

            $method = $prediction->method == 2 ? Prediction::METHOD_COSMOMIC : Prediction::METHOD_COSMOPERM;
            $priority = $this->normalizePriority($prediction->priority);

            $predictions = [];

            // Create or reconcile record
            /** @var PredictionStructure[] $structures */
            foreach ($structures as $structure) {
                /** @var Prediction|null $p */
                $p = $structure->predictions()
                    ->where('method_type', $method)
                    ->where('membrane_id', $membrane->id)
                    ->where('temperature', $prediction->temperature)
                    ->first();

                // Already claimed/processed by the live remote pipeline - never touch it.
                if ($p && $p->remote_calculation_id !== null) {
                    $this->line("  - structure #{$structure->id}: [SKIPPED] prediction #{$p->id} is owned by the live remote pipeline.");

                    continue;
                }

                $isNew = ! $p;

                if ($isNew) {
                    $p = Prediction::create([
                        'structure_id' => $structure->id,
                        'membrane_id' => $membrane->id,
                        'method_type' => $method,
                        'temperature' => $prediction->temperature,
                        'result_id' => null,
                        'state' => null,
                        'step' => null,
                        'priority' => $priority,
                        'created_at' => $prediction->create_date,
                        'updated_at' => $prediction->last_update,
                    ]);
                }

                $predictions[] = $p;

                $label = $isNew ? 'new' : "existing, state={$p->state}, step={$p->step}";

                // Already has a result from a previous run - just make sure the file
                // genuinely still exists and mark the prediction finished accordingly.
                if ($p->result_id && $p->predictionResult?->file?->exists()) {
                    if ($p->state !== Prediction::STATE_FINISHED || $p->step !== Prediction::STEP_RESULT_DB_STORE) {
                        $p->step = Prediction::STEP_RESULT_DB_STORE;
                        $p->state = Prediction::STATE_FINISHED;
                        $p->save();

                        $this->line("  - structure #{$structure->id}: [FINISHED] prediction #{$p->id} ({$label}) - verified result file exists.");
                    } else {
                        $this->line("  - structure #{$structure->id}: [unchanged: finished] prediction #{$p->id}.");
                    }

                    continue;
                }

                // Legacy worker reported a hard failure for this job - keep it failed, do not requeue it.
                if ((int) $prediction->status === self::OLD_STATUS_ERROR) {
                    $p->step = Prediction::STEP_PENDING;
                    $p->state = Prediction::STATE_ERROR;
                    $p->save();

                    $this->line("  - structure #{$structure->id}: [FAILED] prediction #{$p->id} ({$label}) - legacy status reported an error.");

                    continue;
                }

                // No verified result and no error - let the new prediction pipeline pick it up from scratch.
                $p->step = Prediction::STEP_PENDING;
                $p->state = Prediction::STATE_PREPARED;
                $p->save();

                $this->line("  - structure #{$structure->id}: [PENDING] prediction #{$p->id} ({$label}) - no verified result, queued.");

                Log::info("predictions:update - prediction {$p->id} (legacy run_cosmo id {$prediction->id}) has no verified result; queued for the new prediction pipeline.");
            }

            $remote_datasets = $old_db->table('run_cosmo_datasets')
                ->join('run_cosmo_cosmo_datasets', function ($join) use ($prediction) {
                    $join->on('run_cosmo_cosmo_datasets.id_cosmo_dataset', '=', 'run_cosmo_datasets.id')
                        ->where('run_cosmo_cosmo_datasets.id_run_cosmo', '=', $prediction->id);
                })
                ->select('run_cosmo_datasets.*')
                ->distinct()
                ->get();

            // Check if datasets exists
            foreach ($remote_datasets as $o_dataset) {
                $dataset = PredictionDataset::firstOrCreate([
                    'id' => $o_dataset->id,
                ], [
                    'token' => $o_dataset->token,
                    'user_id' => $o_dataset->id_user,
                    'comment' => $o_dataset->comment,
                    'priority' => $priority,
                    'temperature' => $prediction->temperature,
                    'membrane_id' => $membrane->id,
                    'method_type' => $method,
                    'created_at' => $o_dataset->create_date,
                    'updated_at' => $o_dataset->create_date,
                ]);

                $dataset->predictions()->attach($predictions);
            }
        }

        $this->info('Done.');
    }
}
