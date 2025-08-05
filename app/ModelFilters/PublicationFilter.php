<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PublicationFilter extends ModelFilter
{
    public function query($name)
    {
        $name = strtolower($name);

        if(str_starts_with($name, 'id:'))
        {
            return $this->where('id', str_replace('id:', '', $name));
        }

        return $this->whereRaw('LOWER(citation) LIKE ?', ['%' . $name. '%']);
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
