<?php

namespace App\Http\Resources\Api\Public\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\References\EuropePMC\Enums\Sources;

class PublicationReferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pmid' => $this->identifier_source === Sources::MED->value ? $this->identifier : null,
            'citation' => $this->citation,
        ];
    }
}
