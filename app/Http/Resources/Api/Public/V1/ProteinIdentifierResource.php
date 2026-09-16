<?php

namespace App\Http\Resources\Api\Public\V1;

use App\Models\ProteinIdentifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProteinIdentifierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => strtolower(ProteinIdentifier::enumType($this->type)),
            'value' => $this->value,
            'verified' => $this->when($this->state === ProteinIdentifier::STATE_NEW, false),
        ];
    }
}
