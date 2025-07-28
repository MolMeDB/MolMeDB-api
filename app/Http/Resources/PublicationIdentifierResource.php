<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\References\EuropePMC\EuropePMC;

class PublicationIdentifierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'source' => $this->when($this->identifier, $this->identifier_source),
            'source_name' => $this->when($this->identifier, \Modules\References\EuropePMC\Enums\Sources::tryFrom($this->identifier_source)?->definition()),
            'value' => $this->identifier,
        ];
    }
}
