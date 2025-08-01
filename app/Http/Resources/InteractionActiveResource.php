<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteractionActiveResource extends JsonResource
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
            'id' => $this->id,
            'dataset' => DatasetResource::make($this->dataset),
            'structure_id' => $this->structure_id,
            'protein' => ProteinResource::make($this->protein),
            'note' => $this->note,
            'temperature' => $this->temperature,
            'charge' => $this->charge,
            'ph' => $this->ph,
            'measurements' => [
                'km' => $this->getAccuracyValue('km'),
                'ki' => $this->getAccuracyValue('ki'),
                'ec50' => $this->getAccuracyValue('ec50'),
                'ic50' => $this->getAccuracyValue('ic50'),
            ],
            'primary_reference' => PublicationResource::make($this->publication)->ignoreStats(),
            'secondary_reference' => PublicationResource::make($this->dataset->publication)->ignoreStats(),
        );  
    }

    private function getAccuracyValue(string $key)
    {
        $acc_key = $key . '_accuracy';
        return [
            'value' => $this->$key,
            'accuracy' => $this->$acc_key
        ];
    }
}
