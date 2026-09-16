<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PredictionStructureFilter extends ModelFilter
{
    public $sortBy = "id";

    public function sortBy($column)
    {
        $this->sortBy = $column;
        return $this;
    }

    public function sortByDirection($direction)
    {
        if(strtolower($direction) !== 'asc')
        {
            return $this->orderBy($this->sortBy, 'desc');
        }
        return $this->orderBy($this->sortBy, 'asc');
    }

    public function datasetId($id)
    {
        return $this->whereHas('predictions.predictionDatasets', function ($q) use ($id) {
            $q->where('datasets.id', $id); 
        });
    }

    public function setup()
    {
    }
}
