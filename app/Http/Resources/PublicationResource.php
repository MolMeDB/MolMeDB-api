<?php

namespace App\Http\Resources;

use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicationResource extends JsonResource
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
            'identifier' => PublicationIdentifierResource::make($this),
            'citation' => $this->citation,
            'title' => $this->title,
            'doi' => $this->doi,
            'journal' => $this->journal,
            'volume' => $this->volume,
            'issue' => $this->issue,
            'page' => $this->page,
            'year' => $this->year,
            'authors' => AuthorResource::collection($this->authors),
            'stats' => [
                'passive_interactions' => $this->whenCounted('interactionsPassive'),
                'active_interactions' => $this->whenCounted('interactionsActive'),
                'membranes' => $this->whenCounted('membranes'),
                'methods' => $this->whenCounted('methods'),
                'datasets' => $this->whenCounted('datasets'),
                'dataset_passive_interactions' => $this->whenCounted('interactionsPassive', 
                    Publication::find($this->id)
                        ->datasets()
                        ->withCount('interactionsPassive')
                        ->get()
                        ->sum('interactions_passive_count')
                ),
                'dataset_active_interactions' => $this->whenCounted('interactionsActive', 
                    Publication::find($this->id)
                        ->datasets()
                        ->withCount('interactionsActive')
                        ->get()
                        ->sum('interactions_active_count')
                ),
                'authors' => $this->whenCounted('authors'),
            ]
        ];
    }
}
