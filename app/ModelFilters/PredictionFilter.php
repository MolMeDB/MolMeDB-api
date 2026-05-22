<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PredictionFilter extends ModelFilter
{
    public $sortBy = 'id';

    public function sortBy($column)
    {
        $this->sortBy = $column;

        return $this;
    }

    public function sortByDirection($direction)
    {
        if (strtolower($direction) !== 'asc') {
            return $this->orderBy($this->sortBy, 'desc');
        }

        return $this->orderBy($this->sortBy, 'asc');
    }

    public function datasetId($id)
    {
        return $this->whereRelation('predictionDatasets', 'datasets.id', $id);
    }

    public function structureId($id)
    {
        return $this->whereRelation('predictionStructure', 'structures.id', $id);
    }

    public function hasResults($value)
    {
        if ($value) {
            return $this->whereHas('predictionResult');
        }

        return $this;
    }

    public function state($states)
    {
        $states = $this->integerFilterValues($states);

        if ($states === []) {
            return $this;
        }

        return $this->whereIn('state', $states);
    }

    public function step($steps)
    {
        $steps = $this->integerFilterValues($steps);

        if ($steps === []) {
            return $this;
        }

        return $this->whereIn('step', $steps);
    }

    public function query($value)
    {
        if (! $value) {
            return $this;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return $this;
        }

        if (str_starts_with(strtolower($value), 'id:')) {
            $id = trim(substr($value, 3));

            if (is_numeric($id)) {
                return $this->where('predictions.id', (int) $id);
            }
        }

        return $this->where(function ($query) use ($value) {
            if (is_numeric($value)) {
                $query
                    ->orWhere('predictions.id', (int) $value)
                    ->orWhereRelation('predictionStructure', 'id', (int) $value);
            }

            $query->orWhereRelation(
                'predictionStructure',
                'canonical_smiles',
                'ILIKE',
                '%'.$value.'%'
            );
        });
    }

    public function setup() {}

    /**
     * @return array<int>
     */
    private function integerFilterValues(mixed $values): array
    {
        if ($values === null || $values === '') {
            return [];
        }

        if (is_string($values)) {
            $values = explode(',', $values);
        }

        if (! is_array($values)) {
            $values = [$values];
        }

        return collect($values)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }
}
