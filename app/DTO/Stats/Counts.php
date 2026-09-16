<?php
namespace App\DTO\Stats;

class Counts
{
    public int $total_passive_interactions = 0;
    public int $total_active_interactions = 0;
    public int $total_structures = 0;
    public int $total_membranes = 0;
    public int $total_methods = 0;
    public int $total_proteins = 0;

    public function __construct(){}

    public static function from(array $data) : self
    {
        $d = new self();
        $d->total_passive_interactions = $data['total_passive_interactions'] ?? 0;
        $d->total_active_interactions = $data['total_active_interactions'] ?? 0;
        $d->total_structures = $data['total_structures'] ?? 0;
        $d->total_membranes = $data['total_membranes'] ?? 0;
        $d->total_methods = $data['total_methods'] ?? 0;
        $d->total_proteins = $data['total_proteins'] ?? 0;

        return $d;
    }

    public function toArray() : array
    {
        return [
            'total_passive_interactions' => $this->total_passive_interactions,
            'total_active_interactions' => $this->total_active_interactions,
            'total_structures' => $this->total_structures,
            'total_membranes' => $this->total_membranes,
            'total_methods' => $this->total_methods,
            'total_proteins' => $this->total_proteins
        ];
    }
}