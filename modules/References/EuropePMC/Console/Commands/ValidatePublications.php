<?php

namespace Modules\References\EuropePMC\Console\Commands;

use App\Models\Author;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Modules\References\EuropePMC\Enums\Sources;
use Illuminate\Support\Str;

class ValidatePublications extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'europepmc:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes all not-validated publications and validates them if possible.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting validation of all not-validated publications...');

        $europePMC = new \Modules\References\EuropePMC\EuropePMC();

        if(!$europePMC->connected()) {
            $this->fail('EuropePMC is not connected. Stopping...');
            return;
        }

        $publications = \App\Models\Publication::whereNotNull('identifier_source')
            ->whereNotNull('identifier')
            ->whereNull('validated_at')
            ->orderby('id', 'asc')
            ->get();

        $progressBar = $this->output->createProgressBar(count($publications));

        foreach($publications as $publication) 
        {
            $this->info('Validating publication ' . $publication->id);
            $record = $europePMC->detail($publication->identifier, Sources::tryFrom($publication->identifier_source));

            $progressBar->advance();

            if($record)
            {
                $publication->title = $record->title;
                $publication->journal = $record->journal?->title;
                $publication->volume = $record->journal?->volume;
                $publication->issue = $record->journal?->issue;
                $publication->page = $record->pageInfo;
                $publication->year = $record->journal?->yearOfPublication;
                $publication->validated_at = now();

                $publication->save();

                $publication->authors()->detach();

                foreach($record->authors as $author)
                {
                    if(!$author->firstName || !$author->lastName)
                        continue;

                    // Add author if not exists
                    $authorModel = Author::firstOrCreate([
                        'first_name' => $author->firstName,
                        'last_name' => $author->lastName,
                        'full_name' => $author->fullName, 
                        'affiliation' => $author->affiliations && count($author->affiliations) ? Str::limit($author->affiliations[0], 500) : null
                    ]);

                    $publication->authors()->syncWithoutDetaching($authorModel->id);
                }

                $this->info('## Publication validated!');
            }
            else
            {
                $this->warn('## Publication not found on remote server.');
            }
        }

        $progressBar->finish();

        // Remove all unassigned authors
        $toDelete = Author::whereDoesntHave('publications')->count();

        if($toDelete > 0)
        {
            $this->warn('Deleting ' . $toDelete . ' unassigned authors...');
            Author::whereDoesntHave('publications')->delete();
        }

        $not_validated = \App\Models\Publication::whereNull('validated_at')
            ->count();

        $this->info('Done! Total ' . $not_validated . ' publications remaining not validated.');
    }
}
