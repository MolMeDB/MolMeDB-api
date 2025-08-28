<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PublicationFilter extends ModelFilter
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

    public function query($name)
    {
        if(!$name)
        {
            return $this;
        }

        $name = strtolower($name);

        if(str_starts_with($name, 'id:'))
        {
            return $this->where('id', str_replace('id:', '', $name));
        }

        return $this->whereRaw('LOWER(citation) LIKE ?', ['%' . $name. '%']);
    }

    public function setup()
    {
    }
}
