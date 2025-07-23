<?php

namespace App\Http\Resources;

use App\Models\ProteinIdentifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProteinIdentifierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
            'type' => ProteinIdentifier::enumType($this->type),
            'state' => ProteinIdentifier::enumState($this->state),
        ];
    }
}
