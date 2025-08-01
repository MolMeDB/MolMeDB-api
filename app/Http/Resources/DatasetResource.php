<?php

namespace App\Http\Resources;

use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DatasetResource extends JsonResource
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
            'id' => $this->id,
            'type' => $this->type,
            'enum_type' => Dataset::enumType($this->type),
            'name' => $this->name,
            // 'comment' => $this->comment,
            'membrane' => MembraneResource::make($this->membrane)->ignoreDescription(),
            'method' => MethodResource::make($this->method)->ignoreDescription()
        );
    }
}
