<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PublicationFilter extends ModelFilter
{
    public function query($name)
    {
        return $this->whereRaw('LOWER(citation) LIKE ?', ['%' . strtolower($name) . '%']);
    }

    // public function sortby($name)
    // {
    //     return $this->orderBy($name, 'asc');
    // }

    public function setup()
    {
        $this->defaultOrder();
    }

    public function defaultOrder()
    {
        $this->orderBy('title', 'asc');
    }
}
