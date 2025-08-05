<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class MembraneFilter extends ModelFilter
{
    public function query($name)
    {
        return $this->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($name) . '%'])
            ->orWhereRaw('LOWER(abbreviation) LIKE ?', ['%' . strtolower($name) . '%']);
    }

    public function setup()
    {
        $this->defaultOrder();
    }

    public function defaultOrder()
    {
        $this->orderBy('id', 'asc');
    }
}
