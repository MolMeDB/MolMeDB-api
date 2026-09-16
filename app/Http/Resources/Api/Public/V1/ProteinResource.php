<?php

namespace App\Http\Resources\Api\Public\V1;

use App\Models\ProteinIdentifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProteinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uniprot_id' => $this->uniprot_id,
            'identifiers' => $this->whenLoaded('identifiers', fn () => ProteinIdentifierResource::collection(
                $this->identifiers
                    ->whereIn('state', [ProteinIdentifier::STATE_NEW, ProteinIdentifier::STATE_VALIDATED])
                    ->values()
            )),
        ];
    }
}
