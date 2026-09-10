<?php

namespace App\Http\Resources\Api\Public\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\References\EuropePMC\Enums\Sources;

class PublicationResource extends JsonResource
{
    private bool $detailed = false;

    public function withDetails(): self
    {
        $this->detailed = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'citation' => $this->citation,
            'title' => $this->title,
            'doi' => $this->doi,
            'pmid' => $this->identifier_source === Sources::MED->value ? $this->identifier : null,
            'year' => $this->year,
            'journal' => $this->when($this->detailed, $this->journal),
            'volume' => $this->when($this->detailed, $this->volume),
            'issue' => $this->when($this->detailed, $this->issue),
            'page' => $this->when($this->detailed, $this->page),
            'authors' => $this->when($this->detailed, fn () => AuthorResource::collection($this->authors)),
        ];
    }
}
