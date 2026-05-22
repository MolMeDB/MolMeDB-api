<?php

namespace App\Http\Resources;

use App\Models\Identifier;
use App\Models\User;
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
            'source' => $this->getSourceResource(),
        ];
    }

    protected function getSourceResource(): mixed
    {
        if (! $this->includeSource || ! $this->source_id || ! $this->source_type) {
            return null;
        }

        return match ($this->source_type) {
            Identifier::class => [
                'type' => 'identifier',
                'data' => IdentifierResource::make(
                    Identifier::query()->find($this->source_id)
                )->withoutSource(),
            ],
            User::class => [
                'type' => 'user',
                'data' => UserResource::make(
                    User::query()->find($this->source_id)
                ),
            ],
            default => null,
        };
    }
}
