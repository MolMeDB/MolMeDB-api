<?php

namespace App\Services;

use App\Models\InteractionActive;
use App\Models\InteractionPassive;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Builder;

class DownloaderFilterService
{
    /**
     * @param  int[]  $membraneIds
     * @param  int[]  $methodIds
     * @param  string[]  $structureIdentifiers
     */
    public function passiveQuery(array $membraneIds, array $methodIds, array $structureIdentifiers): Builder
    {
        $query = InteractionPassive::query();

        if (blank($membraneIds) && blank($methodIds) && blank($structureIdentifiers)) {
            $query->whereIn($query->getModel()->qualifyColumn('id'), []);

            return $query;
        }

        if (filled($membraneIds) || filled($methodIds)) {
            $query->whereHas('dataset', function (Builder $dataset) use ($membraneIds, $methodIds) {
                if (filled($membraneIds)) {
                    $dataset->whereIn('membrane_id', $membraneIds);
                }

                if (filled($methodIds)) {
                    $dataset->whereIn('method_id', $methodIds);
                }
            });
        }

        return $this->applyStructureFilter($query, $structureIdentifiers);
    }

    /**
     * @param  string[]  $structureIdentifiers
     * @param  int[]  $proteinIds
     */
    public function activeQuery(array $structureIdentifiers, array $proteinIds): Builder
    {
        $query = InteractionActive::query();

        if (blank($structureIdentifiers) && blank($proteinIds)) {
            $query->whereIn($query->getModel()->qualifyColumn('id'), []);

            return $query;
        }

        if (filled($proteinIds)) {
            $query->whereIn('protein_id', $proteinIds);
        }

        return $this->applyStructureFilter($query, $structureIdentifiers);
    }

    /**
     * @param  string[]  $structureIdentifiers
     */
    protected function applyStructureFilter(Builder $query, array $structureIdentifiers): Builder
    {
        if (filled($structureIdentifiers)) {
            $structureIds = Structure::query()
                ->whereIn('identifier', $structureIdentifiers)
                ->pluck('id');

            $query->whereIn('structure_id', $structureIds);
        }

        return $query;
    }
}
