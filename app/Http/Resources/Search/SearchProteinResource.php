<?php

namespace App\Http\Resources\Search;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchProteinResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return
        [
            'title' => $this->matched_identifier ?? $this->uniprot_id,
            'subtitle' => $this->uniprot_id,
            'description' => null,
            'link' => "/browse/proteins?id=$this->id",
            'imageUrl' => null,
            'downloader' => [
                'category' => 'protein',
                'id' => (string) $this->id,
                'label' => $this->uniprot_id,
            ],
        ];
    }
}
