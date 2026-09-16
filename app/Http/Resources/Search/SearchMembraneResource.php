<?php

namespace App\Http\Resources\Search;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchMembraneResource extends JsonResource
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
            'title' => $this->abbreviation,
            'subtitle' => $this->name,
            'description' => null,
            'link' => "/browse/membranes?id=$this->id",
            'imageUrl' => null,
            'downloader' => [
                'category' => 'membrane',
                'id' => (string) $this->id,
                'label' => $this->name,
            ],
        ];
    }
}
