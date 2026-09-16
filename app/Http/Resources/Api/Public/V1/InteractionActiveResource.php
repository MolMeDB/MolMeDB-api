<?php

namespace App\Http\Resources\Api\Public\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteractionActiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'protein' => $this->protein?->uniprot_id,
            'temperature' => $this->temperature,
            'ph' => $this->ph,
            'charge' => $this->charge,
            'note' => $this->note,
            'km' => $this->km,
            'km_accuracy' => $this->km_accuracy,
            'ec50' => $this->ec50,
            'ec50_accuracy' => $this->ec50_accuracy,
            'ki' => $this->ki,
            'ki_accuracy' => $this->ki_accuracy,
            'ic50' => $this->ic50,
            'ic50_accuracy' => $this->ic50_accuracy,
            'primary_reference' => $this->publication ? PublicationReferenceResource::make($this->publication) : null,
            'secondary_reference' => $this->dataset?->publications?->first()
                ? PublicationReferenceResource::make($this->dataset->publications->first())
                : null,
        ];
    }
}
