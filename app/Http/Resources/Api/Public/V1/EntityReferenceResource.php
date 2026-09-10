<?php

namespace App\Http\Resources\Api\Public\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal reference to a membrane/method, enough for a client to follow up
 * with GET /membranes/{id} or GET /methods/{id}.
 */
class EntityReferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
        ];
    }
}
