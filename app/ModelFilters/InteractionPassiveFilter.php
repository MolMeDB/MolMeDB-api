<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class InteractionPassiveFilter extends ModelFilter
{
    public $sortBy = "id";

    public function membraneIds($ids)
    {
        return $this->whereHas('dataset', function ($q) use ($ids) {
            $q->whereIn('membrane_id', $ids);
        });
    }

    public function methodIds($ids)
    {
        return $this->whereHas('dataset', function ($q) use ($ids) {
            $q->whereIn('method_id', $ids);
        });
    }

    public function structureId($id)
    {
        return $this->where('structure_id', $id);
    }

    public function sortBy($column)
    {
        $this->sortBy = $this->translateSortBy($column);
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

    public function setup()
    {
        $this->select('interactions_passive.*');
    }

    private function translateSortBy($key) 
    {
        $translated = match($key) {
            'membrane' => 'mem.abbreviation',
            'method' => 'met.abbreviation',
            default => $key
        };

        if(str_starts_with($translated, "mem."))
        {
            $this->join('datasets as d', 'd.id', '=', 'interactions_passive.dataset_id');
            $this->join('membranes as mem', 'mem.id', '=', 'd.membrane_id');
            return $translated;
        }
        else if(str_starts_with($translated, "met."))
        {
            $this->join('datasets as d', 'd.id', '=', 'interactions_passive.dataset_id');
            $this->join('methods as met', 'met.id', '=', 'd.method_id');
            return $translated;
        }

        return $translated;
    }

}
