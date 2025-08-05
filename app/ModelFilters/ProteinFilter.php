<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class ProteinFilter extends ModelFilter
{
    public function query($name)
    {
        return $this->join('protein_identifiers as i', 'i.protein_id', '=', 'proteins.id')
            ->whereRaw('LOWER(i.value) LIKE ?', ['%' . strtolower($name) . '%'])
            ->orWhereRaw('LOWER(uniprot_id) LIKE ?', ['%' . strtolower($name) . '%'])
            ->select('proteins.*');
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
