<?php

namespace App\Http\Resources\Api\Public\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteractionPassiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'membrane' => $this->dataset?->membrane ? EntityReferenceResource::make($this->dataset->membrane) : null,
            'method' => $this->dataset?->method ? EntityReferenceResource::make($this->dataset->method) : null,
            'temperature' => $this->temperature,
            'ph' => $this->ph,
            'charge' => $this->charge,
            'note' => $this->note,
            'x_min' => $this->x_min,
            'x_min_accuracy' => $this->x_min_accuracy,
            'gpen' => $this->gpen,
            'gpen_accuracy' => $this->gpen_accuracy,
            'gwat' => $this->gwat,
            'gwat_accuracy' => $this->gwat_accuracy,
            'logk' => $this->logk,
            'logk_accuracy' => $this->logk_accuracy,
            'logperm' => $this->logperm,
            'logperm_accuracy' => $this->logperm_accuracy,
            'primary_reference' => $this->publication ? PublicationReferenceResource::make($this->publication) : null,
            'secondary_reference' => $this->dataset?->publications?->first()
                ? PublicationReferenceResource::make($this->dataset->publications->first())
                : null,
        ];
    }
}
