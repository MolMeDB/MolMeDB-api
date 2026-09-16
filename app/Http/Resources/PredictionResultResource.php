<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PredictionResultResource extends JsonResource
{
    protected bool $parseResults = false;

    public function withParsedResults(bool $parse = true): static
    {
        $this->parseResults = $parse;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file' => FileResource::make($this->file),
            'results' => $this->when($this->parseResults, $this->loadParsedResults())
        ];
    }
}
