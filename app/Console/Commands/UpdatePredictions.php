<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\PredictionWorkers\Models\Prediction;

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

    private function old_conformer_folder($id_fragment, $id_ion = null)
    {
        $group = intval($id_fragment / 10000);
        $group = $group*10000;
        $group .= '-' . ($group+10000); 

        if(!$id_ion)
        {
            return $group . '/' . $id_fragment . "/";
        }

        return $group . '/' . $id_fragment . "/" . $id_ion . '/';
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $old_db = DB::connection('molmedb_old');
        $filesystem = Storage::disk('cosmo_runner');

        $q =  $old_db->table('run_cosmo')
            // ->skip(81017)
            ->take(30000) 
            ->orderBy('id');

        $total = 30000;

        $this->warn('Total ' . $total . ' records will be processed.');

        $predictions = $q->cursor();

        $progress = $this->output->createProgressBar($total);

        foreach ($predictions as $prediction) 
        {
            $progress->advance();

            // if(\Modules\PredictionWorkers\Models\Prediction::find($prediction->id))
            // {
            //     continue;
            // }

            $ions = $old_db->table('fragments_ionized')
                ->where('id_fragment', $prediction->id_fragment)
                ->get();

            $structures = [];

            if(count($ions))
            {
                foreach($ions as $ion)
                {
                    // Make structure
                    $s = \Modules\PredictionWorkers\Models\PredictionStructure::firstOrCreate([
                        // 'id' => $ion->id
                        'canonical_smiles' => $ion->smiles,
                    ], [
                        // 'id' => $ion->id,
                        'base_path' => $this->old_conformer_folder($prediction->id_fragment, $ion->id)
                    ]);

                    $structures[] = $s;
                    
                    $s->total_conformers = $s->totalRemoteConformers();
                    $s->save();
                }
            }
            else
            {
                $fragment = $old_db->table('fragments')
                    ->where('id', $prediction->id_fragment)
                    ->first();

                if(!$fragment)
                {
                    continue;
                }

                // Make structure
                $s = \Modules\PredictionWorkers\Models\PredictionStructure::firstOrCreate([
                    'canonical_smiles' => $fragment->smiles
                ], [
                    'base_path' => $this->old_conformer_folder($prediction->id_fragment)
                ]);

                $structures[] = $s;
            }

            $membrane = \Modules\PredictionWorkers\Models\PredictionMembrane::find($prediction->id_membrane);
            if(!$membrane)
            {
                $membrane_remote = $old_db->table('membranes')
                    ->where('id', $prediction->id_membrane)
                    ->first();

                $membrane = \Modules\PredictionWorkers\Models\PredictionMembrane::firstOrCreate([
                    'id' => $membrane_remote->id
                ], [
                    'id' => $membrane_remote->id,
                    'name' => $membrane_remote->name,
                    'abbreviation' => $membrane_remote->CAM
                ]);
            }

            $method = $prediction->method == 2 ? Prediction::METHOD_COSMOMIC : Prediction::METHOD_COSMOPERM;

            $predictions = [];

            // Create record
            /** @var \Modules\PredictionWorkers\Models\PredictionStructure[] $structures */
            foreach($structures as $structure)
            {
                if($structure->predictions()
                    ->where('method_type', $method)
                    ->where('membrane_id', $membrane->id)
                    ->where('temperature', $prediction->temperature)
                    ->exists()
                )
                {
                    continue;
                }

                $p = \Modules\PredictionWorkers\Models\Prediction::create([
                    // 'id' => $prediction->id,
                    'structure_id' => $structure->id,
                    'membrane_id' => $membrane->id,
                    'method_type' => $method,
                    'temperature' => $prediction->temperature,
                    'result_id' => null,
                    'state' => null,
                    'step' => null,
                    'priority' => $prediction->priority,
                    'created_at' => $prediction->create_date,
                    'updated_at' => $prediction->last_update
                ]);

                $predictions[] = $p;

                if(!count($ions))
                {
                    $p->step = Prediction::STEP_PENDING;
                    $p->state = Prediction::STATE_PREPARED;

                    $p->save();
                    continue;
                }

                /** Use the same filesystem */
                $structure->remote($filesystem);

                /** @var \Modules\PredictionWorkers\Models\Prediction $p */

                $step = Prediction::STEP_PENDING;
                $state = Prediction::STATE_PREPARED;

                // Get state and step
                if($structure->isOptimizationDefined())
                {
                    $step = Prediction::STEP_OPTIMIZATION;
                    $state = Prediction::STATE_PREPARED;
                }

                if($structure->areOptimizationsFinished())
                {
                    $step = Prediction::STEP_OPTIMIZATION;
                    $state = Prediction::STATE_FINISHED;
                }

                if($structure->arePreparedCosmoFiles())
                {
                    $step = Prediction::STEP_COSMO;
                    $state = Prediction::STATE_PREPARED;
                }

                if($structure->hasCosmoResults($p))
                {
                    $step = Prediction::STEP_COSMO;
                    $state = Prediction::STATE_FINISHED;

                    // Download and save results
                    if(!$p->predictionResult?->file && $structure->saveCosmoResult($p))
                    {
                        $step = Prediction::STEP_RESULT_DB_STORE;
                        $state = Prediction::STATE_FINISHED;

                        // Save result record
                        $result = $p->predictionResult()->firstOrCreate([]);
                        
                        $file = $result->file()->firstOrCreate([
                            'storage' => 'remote-predictions',
                            'path' => $structure::getLocalCosmoFilePath($p)
                        ]);

                        $result->file()->associate($file);
                        $result->save();

                        $p->predictionResult()->associate($result);
                        $p->save();
                    }
                }

                $p->state = $state;
                $p->step = $step;
                $p->save();
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
            foreach($remote_datasets as $o_dataset)
            {
                $dataset = \Modules\PredictionWorkers\Models\PredictionDataset::firstOrCreate([
                    'id' => $o_dataset->id
                ], [
                    'token' => $o_dataset->token,
                    'user_id' => $o_dataset->id_user,
                    'comment' => $o_dataset->comment,
                    'priority' => $prediction->priority,
                    'temperature' => $prediction->temperature,
                    'membrane_id' => $membrane->id,
                    'method_type' => $method,
                    'created_at' => $o_dataset->create_date,
                    'updated_at' => $o_dataset->create_date
                ]);

                $dataset->predictions()->attach($predictions);
            }
        }

        $this->info('Done.');
    }
}
