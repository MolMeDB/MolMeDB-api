<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Identifier;
use App\Models\InteractionActive;
use App\Models\Structure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

class UpdateStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:update-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates all statistics in the database and cache them to redis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating statistics...');

        // Update counts 
        $this->warn('... 1) Updating counts statistics');
        $counts = \App\DTO\Stats\Counts::from([
            'total_passive_interactions' => \App\Models\InteractionPassive::count(),
            'total_active_interactions' => \App\Models\InteractionActive::count(),
            'total_structures' => \App\Models\Structure::count(),
            'total_membranes' => \App\Models\Membrane::count(),
            'total_methods' => \App\Models\Method::count(),
        ]);
        \App\Models\Stats::setCountStats($counts);
        $this->info('... 1) Finished.');

        // Update interaction substance history
        $this->warn('... 2) Updating interaction substance chart counts');
        $minDate = Date::parse(\App\Models\Structure::min('created_at'))->addMonth()->startOfMonth();
        $maxDate = now()->startOfMonth();

        $totalMonths = $minDate->diffInMonths($maxDate) + 1;
        $step = floor($totalMonths 
                      / 22); // Maximum number of bars

        $steps = range(0, $totalMonths, $step);
        $data = [];

        foreach($steps as $i)
        {
            $date = $minDate->copy()->addMonths($i);
            $this->info('... ## Processing date: ' . $date->format('m/Y'));
            $data[] = \App\DTO\Stats\LineChart::makeItem(
                $date->valueOf(),
                \App\Models\Structure::where('created_at', '<=', $date->endOfMonth())
                    ->count(),
                \App\Models\InteractionActive::where('created_at', '<=', $date->endOfMonth())
                    ->count() 
                + \App\Models\InteractionPassive::where('created_at', '<=', $date->endOfMonth())
                    ->count()
                
            );
        }

        \App\Models\Stats::setInteractionSubstanceHistory(
            \App\DTO\Stats\LineChart::from($data)
        );
        $this->info('... 2) Finished.');

        // Update databases bar counts
        $this->warn('... 3) Updating databases bar counts');
        $databases = [
            Identifier::TYPE_PUBCHEM => Identifier::enumType(Identifier::TYPE_PUBCHEM),
            Identifier::TYPE_DRUGBANK => Identifier::enumType(Identifier::TYPE_DRUGBANK),
            Identifier::TYPE_CHEBI => Identifier::enumType(Identifier::TYPE_CHEBI),
            Identifier::TYPE_PDB => Identifier::enumType(Identifier::TYPE_PDB),
            Identifier::TYPE_CHEMBL => Identifier::enumType(Identifier::TYPE_CHEMBL),
        ];

        $data = [];
        foreach($databases as $type => $name)
        {
            $count = Identifier::where('type', $type)
                ->select('structure_id')
                ->distinct()->count();
            $data[] = \App\DTO\Stats\BarChart::makeItem(
                $name,
                $count
            );
        }

        \App\Models\Stats::setDatabasesBarData(
            \App\DTO\Stats\BarChart::from($data)
        );
        $this->info('... 3) Finished.');

        // Update proteins bar counts
        $this->warn('... 4) Updating proteins bar counts');
        $proteinGroups = \App\Models\Category::where('type', Category::TYPE_PROTEIN)
            ->where('parent_id', -1)
            ->get();

        $data = [];
        foreach($proteinGroups as $group)
        {
            $this->info('... ## Processing group: ' . $group->title);
            $subcats = [$group->id];
            $last = 0;

            while (count($subcats) > $last) {
                $last = count($subcats);
                $subcats = array_unique(array_merge($subcats, \App\Models\Category::whereIn('parent_id', $subcats)
                    ->pluck('id')
                    ->toArray()));
            }

            $proteins = \App\Models\Protein::whereHas('categories', function ($query) use ($subcats) { 
                    $query->whereIn('category_id', $subcats);})
                ->get();

            $total_interactions = InteractionActive::whereIn('protein_id', $proteins->pluck('id'))
                ->count();

            $data[] = \App\DTO\Stats\BarChart::makeItem(
                $group->title,
                $proteins->count(),
                $total_interactions
            );
        }

        \App\Models\Stats::setProteinBarData(
            \App\DTO\Stats\BarChart::from($data)
        );
        $this->info('... 4) Finished.');

        // Update publication by year stats
        $this->warn('... 5) Updating publication by year stats');
        $minYear = \App\Models\Publication::min('year');
        $minYear =  $minYear - ($minYear % 5);
        $maxYear = date('Y');

        $data = [];

        foreach(range($minYear, $maxYear, 5) as $year)
        {
            $this->info('... ## Processing year: ' . $year);
            $data[] = \App\DTO\Stats\LineChart::makeItem(
                "$year - " . $year + 4,
                \App\Models\InteractionActive::whereHas('publication', function ($query) use ($year) 
                { 
                    $query->whereBetween('year', [$year, $year + 4]); 
                })->count()
                + \App\Models\InteractionPassive::whereHas('publication', function ($query) use ($year) 
                { 
                    $query->whereBetween('year', [$year, $year + 4]);
                })->count(),
                null
            );
        }

        \App\Models\Stats::setPublicationByYearStatsData(
            \App\DTO\Stats\LineChart::from($data)
        );
        $this->info('... 5) Finished.');

        // Update publication by journal counts
        $this->warn('... 6) Updating publications by journal bar counts');
        
        $data = [];

        $journals = \App\Models\Publication::select('journal')
            ->whereNotNull('journal')
            ->distinct()
            ->get();

        foreach ($journals as $journal)
        {
            $this->info('... ## Processing journal: ' . $journal->journal);
            $data[] = \App\DTO\Stats\BarChart::makeItem(
                $journal->journal,
                \App\Models\InteractionActive::whereHas('publication', function ($query) use ($journal) 
                { 
                    $query->where('journal', $journal->journal); 
                })->count()
                + \App\Models\InteractionPassive::whereHas('publication', function ($query) use ($journal) 
                { 
                    $query->where('journal', $journal->journal); 
                })->count(),
            );
        }

        usort($data, function ($a, $b) {
            return $a->value1 < $b->value1;
        });

        $data = array_filter($data, function ($item) {
            return $item->value1 > 100;
        });

        \App\Models\Stats::setPublicationByJournalStatsData(
            \App\DTO\Stats\BarChart::from($data)
        );

        $this->info('... 6) Finished.');
    }
}
