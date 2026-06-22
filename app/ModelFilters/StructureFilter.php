<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;
use Modules\Rdkit\Rdkit;

class StructureFilter extends ModelFilter
{
    public function query($name)
    {
        $query = trim((string) $name);

        return $this->leftJoin('identifiers as i', 'i.structure_id', '=', 'structures.id')
            ->where(function ($builder) use ($query): void {
                $builder
                    ->whereRaw('LOWER(i.value) LIKE ?', ['%'.mb_strtolower($query).'%'])
                    ->orWhere('structures.canonical_smiles', $query);
            })
            ->selectRaw('DISTINCT ON (structures.id) structures.*, i.value as matched_identifier');
    }

    public function smiles($smiles)
    {
        $smiles = trim((string) $smiles);
        $canonicalSmiles = (new Rdkit)->canonize_smiles($smiles);

        return $this->where(
            'structures.canonical_smiles',
            $canonicalSmiles ?: $smiles,
        );
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
