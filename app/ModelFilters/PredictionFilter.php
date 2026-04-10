<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PredictionFilter extends ModelFilter
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
        return $this->whereRelation('predictionDatasets', 'datasets.id', $id);
    }

    public function structureId($id)
    {
        return $this->whereRelation('predictionStructure', 'structures.id', $id);
    }

    public function hasResults($value)
    {
        if($value)
        {
            return $this->whereHas('predictionResult');
        }
        return $this;
    }

    // public function query($comment)
    // {
    //     if(!$comment)
    //     {
    //         return $this;
    //     }

    //     $comment = strtolower($comment);

    //     if(str_starts_with($comment, 'id:'))
    //     {
    //         return $this->where('id', str_replace('id:', '', $comment));
    //     }

    //     return $this->whereRaw('LOWER(comment) LIKE ?', ['%' . $comment. '%'])
    //         ->distinct();
    // }

    public function setup()
    {
    }
}
