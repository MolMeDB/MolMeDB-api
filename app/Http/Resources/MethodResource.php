<?php

namespace App\Http\Resources;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MethodResource extends JsonResource
{
    private $with_description = true;

    public function ignoreDescription() : self {
        $this->with_description = false;
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
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'description' => $this->with_description ? $this->description : null,
            'datasets' => $this->whenLoaded('files', FileResource::collection($this->files->where('type', File::TYPE_EXPORT_INTERACTIONS_METHOD)))
        ]; 
    }
}
