<?php

namespace App\Http\Resources;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'enum_type' => File::enumType($this->type),
            'mime' => $this->mime,
            'name' => $this->name,
            'hash' => $this->hash,
            'created_at' => $this->created_at->format('Y-m-d')
        ];
    }
}
