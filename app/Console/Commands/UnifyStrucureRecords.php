<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Identifier;
use App\Models\InteractionActive;
use App\Models\Structure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Modules\Rdkit\Rdkit;

class UnifyStrucureRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'structures:unify-charges {startId=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Goes through all structures and if the structure is not neutralized, then is added as substructure of the neutralized parent.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Unifying structures...');

        $startId = (int) $this->argument('startId');

        $total = \App\Models\Structure::where('id', '>=', $startId)->count();

        $this->warn('Total ' . $total . ' structures will be processed.');

        $rdkit = new Rdkit();

        if(!$rdkit->is_connected())
        {
            $this->error('Rdkit is not connected. Stopping...');
            return;
        }

        $structures = \App\Models\Structure::where('id', '>=', $startId)
            ->orderBy('id')
            ->cursor();

        $i = 1;
        foreach ($structures as $structure) 
        {
            $percent = round(($i++ / $total) * 100,2);
            $this->info('# ' . $percent . '% - Processing structure ID: ' . $structure->id);

            if(!$structure->canonical_smiles)
            {
                $this->error('Structure ID: ' . $structure->id . ' has no SMILES.');
                continue;
            }

            $canonized_smiles = $rdkit->canonize_smiles($structure->canonical_smiles);

            if(!$canonized_smiles)
            {
                $this->error('Failed to canonize SMILES for structure ID: ' . $structure->id);
                return;
            }

            if($canonized_smiles !== $structure->canonical_smiles)
            {
                $structure->canonical_smiles = $canonized_smiles;
                $structure->save();
                $this->warn('.... invalid structure SMILES has been fixed.');
            }
            
            $repr_smiles = $rdkit->get_representant($structure->canonical_smiles);

            if(!$repr_smiles)
            {
                $this->error('Failed to get representant SMILES for structure ID: ' . $structure->id);
                return;
            }

            if($canonized_smiles === $repr_smiles)
            {
                if($structure->parent?->canonical_smiles === $repr_smiles)
                {
                    $this->error('... Found duplicity. Structure has the same SMILES as its parent.');
                    return;
                }

                if($structure->parent?->exists())
                {
                    $this->danger('Structure is neutralized, but has parent. Please, check the record.');
                    return;
                }

                $this->info('.... OK - structure is self-representative.');
                continue;
            }

            $representant = \App\Models\Structure::where('canonical_smiles', $repr_smiles)->first();

            if($representant && $representant->id == $structure->parent_id)
            {
                $this->info('.... OK - parent ID is correctly assigned.');
                continue;
            }

            if($representant?->exists())
            {
                $structure->setParent($representant);
                $this->warn('.... assigned parent ID: ' . $representant->id);
                continue;
            }
            else
            {
                $generalInfo = $rdkit->get_general_info($repr_smiles);

                if(!$generalInfo)
                {
                    $this->error('Failed to get general info for representant SMILES: ' . $repr_smiles);
                    return;
                }

                $representant = \App\Models\Structure::create([
                    'canonical_smiles' => $repr_smiles,
                    'inchi' => $generalInfo->inchi,
                    'inchikey' => $generalInfo->inchikey,
                    'molecular_weight' => $generalInfo->mw,
                    'logp' => $generalInfo->logp
                ]);

                $structure->setParent($representant);
                $this->warn('.... created and assigned representant with ID: ' . $representant->id);
                continue;
            }
        }

        $this->info('Done.');
        $this->warn('Do not forget to check identifiers of the changed structures. Run: php artisan structures:check-internal-identifiers');
    }
}
