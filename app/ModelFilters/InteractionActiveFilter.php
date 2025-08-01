<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class InteractionActiveFilter extends ModelFilter
{
    public $sortBy = "id";

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
        $this->select('interactions_active.*');
    }

    private function translateSortBy($key) 
    {
        $translated = match($key) {
            default => $key
        };

        return $translated;
    }

}
