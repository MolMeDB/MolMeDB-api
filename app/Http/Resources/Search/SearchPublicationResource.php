<?php
namespace App\Http\Resources\Search;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchPublicationResource extends JsonResource
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
            'title' => $this->title,
            'subtitle' => $this->citation,
            'description' => null,
            'link' => "/browse/datasets?id=$this->id",
            'imageUrl' => null
        );
    }
}
