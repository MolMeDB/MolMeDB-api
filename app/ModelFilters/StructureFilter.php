<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class StructureFilter extends ModelFilter
{
    public function query($name)
    {
        return $this->join('identifiers as i', 'i.structure_id', '=', 'structures.id')
            ->whereRaw('LOWER(i.value) LIKE ?', ['%' . strtolower($name) . '%'])
            ->select('structures.*');
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
