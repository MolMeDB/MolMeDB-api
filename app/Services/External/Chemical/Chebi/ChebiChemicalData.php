<?php
namespace App\Services\External\Chemical\Chebi;

class ChebiChemicalData {
    public function __construct(
        public ?string $formula = null,
        public ?string $charge = null,
        public ?float $molecular_weight = null,
        public ?float $monoisotopic_mass = null,
    )
    {}

    public static function fromApiResponse(array $response) : self 
    {
        return new self(
            formula: $response['chemical_data']['formula'] ?? null,
            charge: $response['chemical_data']['charge'] ?? null,
            molecular_weight: isset($response['chemical_data']['mass']) ? (float) $response['chemical_data']['mass'] : null,
            monoisotopic_mass: isset($response['chemical_data']['monoisotopic_mass']) ? (float) $response['chemical_data']['monoisotopic_mass'] : null,
        );
    }
}