<?php
namespace App\Http\Resources\Search;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchMethodResource extends JsonResource
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
            'title' => $this->abbreviation,
            'subtitle' => $this->name,
            'description' => null,
            'link' => "/browse/methods?id=$this->id",
            'imageUrl' => null
        );
    }
}
