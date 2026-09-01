<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class ProteinFilter extends ModelFilter
{
    public function query($name)
    {
        return $this->join('protein_identifiers as i', 'i.protein_id', '=', 'proteins.id')
            ->where(function ($q) use ($name) {
                $q->whereRaw('LOWER(i.value) LIKE ?', ['%' . strtolower($name) . '%'])
                ->orWhereRaw('LOWER(uniprot_id) LIKE ?', ['%' . strtolower($name) . '%']);
            })
            ->selectRaw('DISTINCT ON (proteins.id) proteins.*, 
                CASE 
                    WHEN LOWER(i.value) LIKE ? THEN i.value 
                    ELSE uniprot_id 
                END as matched_identifier', 
                ['%' . strtolower($name) . '%'])
            ->orderBy('proteins.id')
            ->orderByRaw("CASE 
                WHEN LOWER(uniprot_id) LIKE ? THEN 1
                WHEN LOWER(i.value) LIKE ? THEN 2
                ELSE 3 
            END", ['%' . strtolower($name) . '%', '%' . strtolower($name) . '%']);

        return $this->join('protein_identifiers as i', 'i.protein_id', '=', 'proteins.id')
            ->whereRaw('LOWER(i.value) LIKE ?', ['%' . strtolower($name) . '%'])
            ->orWhereRaw('LOWER(uniprot_id) LIKE ?', ['%' . strtolower($name) . '%'])
            ->selectRaw('DISTINCT ON (proteins.id) proteins.*, i.value as matched_identifier');
            // ->select('proteins.*');
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
