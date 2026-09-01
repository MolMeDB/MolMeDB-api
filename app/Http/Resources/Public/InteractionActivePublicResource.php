<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteractionActivePublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array
        (
            'structure_identifier' => $this->structure?->identifier,
            'structure_canonical_smiles' => $this->structure?->canonical_smiles,
            'uniprot_id' => $this->protein?->uniprot_id,
            'note' => $this->note,
            'temperature' => $this->temperature,
            'charge' => $this->charge,
            'ph' => $this->ph,
            'km' => $this->km,
            'km_accuracy' => $this->km_accuracy,
            'ki' => $this->ki,
            'ki_accuracy' => $this->ki_accuracy,
            'ec50' => $this->ec50,
            'ec50_accuracy' => $this->ec50_accuracy,
            'ic50' => $this->ic50,
            'ic50_accuracy' => $this->ic50_accuracy,
            'primary_reference' => $this->publication?->citation,
            'secondary_reference' => $this->dataset?->publication?->citation
        );  
    }
}
