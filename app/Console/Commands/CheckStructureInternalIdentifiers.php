<?php

namespace App\Console\Commands;

use App\Libraries\Identifiers;
use App\Models\Structure;
use Illuminate\Console\Command;

class CheckStructureInternalIdentifiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'structures:check-internal-identifiers {startId=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Goes through all structures and checks for internal identifiers.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking structures identifiers...');

        $startId = (int) $this->argument('startId');

        $total = \App\Models\Structure::where('id', '>=', $startId)->count();

        $this->warn('Total ' . $total . ' structures will be processed.');

        $structures = \App\Models\Structure::where('id', '>=', $startId)
            ->orderBy('id')
            ->cursor();

        $i = 1;
        foreach ($structures as $structure) 
        {
            $percent = round(($i++ / $total) * 100,2);
            $this->info('# ' . $percent . '% - Processing structure ID: ' . $structure->id);

            // At first, check parent identifier
            if($structure->parent)
            {
                $identifier = Identifiers::generate($structure->parent);

                if(!$identifier)
                {
                    $this->error('Failed to generate identifier for parent structure ID: ' . $structure->parent_id);
                    return;
                }

                if($identifier != $structure->parent->identifier)
                {
                    $structure->parent->changeMainIdentifier($identifier);
                    $this->warn('Parent identifier was changed for structure ID: ' . $structure->id);
                }
            }

            // Reload structure record
            $structure->refresh();

            $identifier = Identifiers::generate($structure);

            if(!$identifier)
            {
                $this->error('Failed to generate identifier for structure ID: ' . $structure->id);
                return;
            }

            if($identifier != $structure->identifier)
            {
                $structure->changeMainIdentifier($identifier);
                $this->warn('Identifier was changed for structure ID: ' . $structure->id);
                continue;
            }

            $this->info('--- OK');
        }

        $this->info('Done.');
    }
}
