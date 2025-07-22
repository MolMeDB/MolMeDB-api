<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->when($request->user()?->isAdmin(), $this->id),
            'parent_id' => $this->when($request->user()?->isAdmin(), $this->parent_id),
            'title' => $this->title,
            'type' => $this::enumType($this->type)
        ];
    }
}
