<?php

namespace App\Console\Commands;

use App\Libraries\ExportFileColumn;
use App\Libraries\ExportFileHeader;
use App\Libraries\ExportToFile;
use App\Models\Category;
use App\Models\Dataset;
use App\Models\File;
use App\Models\Identifier;
use App\Models\InteractionActive;
use App\Models\InteractionPassive;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Publication;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateExportFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:update-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates all files determined for the export.';

    // const CSV_PASSIVE_INTERACTIONS_HEADER = [
    //     ...self::STRUCTURE_INFO_HEADER,
    //     'membrane',
    //     'method',
    //     'temperature',
    //     'charge',
    // ];

    private static function prepareIdentifierQuery($identifier = Identifier::TYPE_NAME)
    {
        return DB::table(
                DB::raw('(SELECT value, structure_id, ROW_NUMBER() OVER (PARTITION BY structure_id ORDER BY state DESC, id ASC) AS rn
                            FROM identifiers
                            WHERE type = ' . $identifier . ' AND state != ' . Identifier::STATE_INVALID . ') as t')
            )
            ->where('rn', 1)
            ->select('value', 'structure_id');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating statistics...');

        $this->info('... 1) Updating membrane exports');
        $membranes = Membrane::cursor();

        foreach($membranes as $membrane)
        {
            $this->warn("\nProcessing membrane {$membrane->abbreviation}");
            $export = new ExportToFile(ExportToFile::CONTEXT_MEMBRANE, null, $membrane->id);

            $export->setHeader(ExportFileHeader::make()
                ->structure()
                ->passiveInteraction()
            )->writeHeader();

            $statebar = $this->output->createProgressBar($membrane->interactionsPassive()->count());

            foreach(DB::query()
                    ->from('interactions_passive')
                    ->join('datasets', 'datasets.id', '=', 'interactions_passive.dataset_id')
                    ->join('membranes as mem', function ($join) use ($membrane){
                        $join->on('mem.id', '=', 'datasets.membrane_id')
                            ->where('mem.id', $membrane->id);
                    })
                    ->join('methods as met', 'met.id', '=', 'datasets.method_id')
                    ->leftJoin('model_has_publications as mhp', function ($join) {
                        $join->on('mhp.model_id', '=', 'datasets.id')
                            ->where('mhp.model_type', Dataset::class);
                    })
                    ->leftJoin('publications as pub2', 'pub2.id', '=', 'mhp.publication_id')
                    ->leftJoin('publications as pub', 'pub.id', '=', 'interactions_passive.publication_id')
                    ->join('structures as s', 's.id', '=', 'interactions_passive.structure_id')
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_NAME), 'name', function($join) {
                        $join->on('s.id', '=', 'name.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_PDB), 'pdb', function($join) {
                        $join->on('s.id', '=', 'pdb.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_PUBCHEM), 'pubchem', function($join) {
                        $join->on('s.id', '=', 'pubchem.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_DRUGBANK), 'drugbank', function($join) {
                        $join->on('s.id', '=', 'drugbank.structure_id');
                    })
                    ->orderBy('interactions_passive.id')
                    ->select(
                        'interactions_passive.*', 
                        's.identifier', 's.canonical_smiles', 's.logp', 's.molecular_weight', 's.inchikey', 
                        'mem.abbreviation as membrane', 
                        'met.abbreviation as method', 
                        'pdb.value as pdb',
                        'pubchem.value as pubchem',
                        'drugbank.value as drugbank',
                        'name.value as name',
                        'pub.citation as primary_citation',
                        'pub2.citation as secondary_citation')
                ->cursor() as $interaction)
            {
                $statebar->advance();
                $export->writeRow($interaction);
            }

            $result = $export->closeFile()
                ->zip()
                ?->keepLastChanged()
                ->deleteFile();

            if($result === null)
            {
                $this->warn("\Membrane has probably no passive interactions.");
            }
            else
            {
                $file = File::firstOrCreate([
                    'path' => $result->getZipFilePath()
                ], [
                    'type' => File::TYPE_EXPORT_INTERACTIONS_MEMBRANE,
                    'name' => basename($result->getZipFilePath())
                ]);

                $membrane->files()->syncWithoutDetaching([ 
                    $file->id => [
                        'model_type' => Membrane::class  
                    ]
                ]);
            }

            $statebar->finish();
        }

        

        $this->info('\n... 2) Updating method exports');
        $methods = Method::cursor();

        foreach($methods as $method)
        {
            $this->warn("\nProcessing method {$method->abbreviation}");
            $export = new ExportToFile(ExportToFile::CONTEXT_METHOD, null, $method->id);

            $export->setHeader(ExportFileHeader::make()
                ->structure()
                ->passiveInteraction()
            )->writeHeader();

            $statebar = $this->output->createProgressBar($method->interactionsPassive()->count());

            foreach(DB::query()
                    ->from('interactions_passive')
                    ->join('datasets', 'datasets.id', '=', 'interactions_passive.dataset_id')
                    ->join('methods as met', function ($join) use ($method){
                        $join->on('met.id', '=', 'datasets.method_id')
                            ->where('met.id', $method->id);
                    })
                    ->leftJoin('model_has_publications as mhp', function ($join) {
                        $join->on('mhp.model_id', '=', 'datasets.id')
                            ->where('mhp.model_type', Dataset::class);
                    })
                    ->leftJoin('publications as pub2', 'pub2.id', '=', 'mhp.publication_id')
                    ->leftJoin('publications as pub', 'pub.id', '=', 'interactions_passive.publication_id')
                    ->join('membranes as mem', 'mem.id', '=', 'datasets.membrane_id')
                    ->join('structures as s', 's.id', '=', 'interactions_passive.structure_id')
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_NAME), 'name', function($join) {
                        $join->on('s.id', '=', 'name.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_PDB), 'pdb', function($join) {
                        $join->on('s.id', '=', 'pdb.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_PUBCHEM), 'pubchem', function($join) {
                        $join->on('s.id', '=', 'pubchem.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_DRUGBANK), 'drugbank', function($join) {
                        $join->on('s.id', '=', 'drugbank.structure_id');
                    })
                    ->orderBy('interactions_passive.id')
                    ->select(
                        'interactions_passive.*', 
                        's.identifier', 's.canonical_smiles', 's.logp', 's.molecular_weight', 's.inchikey', 
                        'mem.abbreviation as membrane', 
                        'met.abbreviation as method', 
                        'pdb.value as pdb',
                        'pubchem.value as pubchem',
                        'drugbank.value as drugbank',
                        'name.value as name',
                        'pub.citation as primary_citation',
                        'pub2.citation as secondary_citation')
                ->cursor() as $interaction)
            {
                $statebar->advance();
                $export->writeRow($interaction);
            }

            $result = $export->closeFile()
                ->zip()
                ?->keepLastChanged()
                ->deleteFile();

            if($result === null)
            {
                $this->warn("\Method has probably no passive interactions.");
            }
            else
            {
                $file = File::firstOrCreate([
                    'path' => $result->getZipFilePath()
                ], [
                    'type' => File::TYPE_EXPORT_INTERACTIONS_METHOD,
                    'name' => basename($result->getZipFilePath())
                ]);

                $method->files()->syncWithoutDetaching([ 
                    $file->id => [
                        'model_type' => Method::class  
                    ]
                ]);
            }

            $statebar->finish();
        }


        $this->info('\n... 3) Updating publication exports');
        $publications = Publication::cursor();

        foreach($publications as $publication)
        {
            $this->info("\nProcessing publication {$publication->id}");

            /// Process passive interactions
            $export = new ExportToFile(ExportToFile::CONTEXT_PUBLICATION, null, $publication->id . '/passive');

            $export->setHeader(ExportFileHeader::make()
                ->structure()
                ->passiveInteraction()
            )->writeHeader();

            $statebar = $this->output->createProgressBar();

            foreach(DB::query()
                    ->from('interactions_passive')
                    ->join('datasets', 'datasets.id', '=', 'interactions_passive.dataset_id')
                    ->leftJoin('model_has_publications as mhp', function ($join) {
                        $join->on('mhp.model_id', '=', 'datasets.id')
                            ->where('mhp.model_type', Dataset::class);
                    })
                    ->leftJoin('publications as pub2', 'pub2.id', '=', 'mhp.publication_id')
                    ->leftJoin('publications as pub', 'pub.id', '=', 'interactions_passive.publication_id')
                    ->join('methods as met', 'met.id', '=', 'datasets.method_id')
                    ->join('membranes as mem', 'mem.id', '=', 'datasets.membrane_id')
                    ->join('structures as s', 's.id', '=', 'interactions_passive.structure_id')
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_NAME), 'name', function($join) {
                        $join->on('s.id', '=', 'name.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_PDB), 'pdb', function($join) {
                        $join->on('s.id', '=', 'pdb.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_PUBCHEM), 'pubchem', function($join) {
                        $join->on('s.id', '=', 'pubchem.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_DRUGBANK), 'drugbank', function($join) {
                        $join->on('s.id', '=', 'drugbank.structure_id');
                    })
                    ->orWhere(function ($query) use ($publication) {
                        $query->where('pub.id', $publication->id)
                            ->orWhere('pub2.id', $publication->id);
                    })
                    ->orderBy('interactions_passive.id')
                    ->select(
                        'interactions_passive.*', 
                        's.identifier', 's.canonical_smiles', 's.logp', 's.molecular_weight', 's.inchikey', 
                        'mem.abbreviation as membrane', 
                        'met.abbreviation as method', 
                        'pdb.value as pdb',
                        'pubchem.value as pubchem',
                        'drugbank.value as drugbank',
                        'name.value as name',
                        'pub.citation as primary_citation',
                        'pub2.citation as secondary_citation')
                ->cursor() as $interaction)
            {
                $statebar->advance();
                $export->writeRow($interaction);
            }

            $result = $export->closeFile()
                ->zip()
                ?->keepLastChanged()
                ->deleteFile();

            if($result === null)
            {
                $this->warn("\nPublication has probably no passive interactions.");
            }
            else
            {
                $file = File::firstOrCreate([
                    'path' => $result->getZipFilePath()
                ], [
                    'type' => File::TYPE_EXPORT_INTERACTIONS_PASSIVE_PUBLICATION,
                    'name' => basename($result->getZipFilePath())
                ]);

                $publication->files()->syncWithoutDetaching([ 
                    $file->id => [
                        'model_type' => Publication::class  
                    ]
                ]);
            }

            /// Process active interactions
            $export = new ExportToFile(ExportToFile::CONTEXT_PUBLICATION, null, $publication->id . '/active');

            $export->setHeader(ExportFileHeader::make()
                ->structure()
                ->activeInteraction()
            )->writeHeader();

            foreach(DB::query()
                    ->from('interactions_active')
                    ->join('datasets', 'datasets.id', '=', 'interactions_active.dataset_id')
                    ->leftJoin('model_has_publications as mhp', function ($join) {
                        $join->on('mhp.model_id', '=', 'datasets.id')
                            ->where('mhp.model_type', Dataset::class);
                    })
                    ->leftJoin('publications as pub2', 'pub2.id', '=', 'mhp.publication_id')
                    ->leftJoin('publications as pub', 'pub.id', '=', 'interactions_active.publication_id')
                    ->join('structures as s', 's.id', '=', 'interactions_active.structure_id')
                    ->join('proteins as p', 'p.id', '=', 'interactions_active.protein_id')
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_NAME), 'name', function($join) {
                        $join->on('s.id', '=', 'name.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_PDB), 'pdb', function($join) {
                        $join->on('s.id', '=', 'pdb.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_PUBCHEM), 'pubchem', function($join) {
                        $join->on('s.id', '=', 'pubchem.structure_id');
                    })
                    ->leftJoinSub(self::prepareIdentifierQuery(Identifier::TYPE_DRUGBANK), 'drugbank', function($join) {
                        $join->on('s.id', '=', 'drugbank.structure_id');
                    })
                    ->orWhere(function ($query) use ($publication) {
                        $query->where('pub.id', $publication->id)
                            ->orWhere('pub2.id', $publication->id);
                    })
                    ->orderBy('interactions_active.id')
                    ->select(
                        'interactions_active.*', 
                        'p.uniprot_id as protein',
                        's.identifier', 's.canonical_smiles', 's.logp', 's.molecular_weight', 's.inchikey', 
                        'pdb.value as pdb',
                        'pubchem.value as pubchem',
                        'drugbank.value as drugbank',
                        'name.value as name',
                        'pub.citation as primary_citation',
                        'pub2.citation as secondary_citation')
                ->cursor() as $interaction)
            {
                $statebar->advance();
                $export->writeRow($interaction);
            }

            $result = $export->closeFile()
                ->zip()
                ?->keepLastChanged()
                ->deleteFile();

            $statebar->finish();

            if($result === null)
            {
                $this->warn("\nPublication has probably no active interactions.");
            }
            else
            {
                $file = File::firstOrCreate([
                    'path' => $result->getZipFilePath()
                ], [
                    'type' => File::TYPE_EXPORT_INTERACTIONS_ACTIVE_PUBLICATION,
                    'name' => basename($result->getZipFilePath())
                ]);

                $publication->files()->syncWithoutDetaching([ 
                    $file->id => [
                        'model_type' => Publication::class  
                    ]
                ]);
            }
        }

        return;
    }
}
