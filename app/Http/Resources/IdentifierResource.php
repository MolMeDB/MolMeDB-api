<?php

namespace App\Http\Resources;

use App\Models\Identifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentifierResource extends JsonResource
{
    protected bool $includeSource = true;

    public function withoutSource(): self
    {
        $this->includeSource = false;
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
            'value' => $this->value,
            'type' => $this->type,
            'enum_type' => Identifier::enumType($this->type),
            'state' => $this->state,
            'enum_state' => Identifier::enumState($this->state),
            'source' => $this->includeSource ? $this->getSourceResource($this->source) : null,
        ];
    }

    protected function getSourceResource($source) {
        if(!$source)
            return null;

        return match(get_class($source)) {
            \App\Models\Identifier::class => [
                'type' => 'identifier', 
                'data' => IdentifierResource::make($source)->withoutSource()
            ],
            \App\Models\User::class => [
                'type' => 'user',
                'data' => UserResource::make($source),
            ],
            default => null
        };
    }
}