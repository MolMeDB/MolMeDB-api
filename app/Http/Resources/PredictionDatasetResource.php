<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PredictionWorkers\Models\PredictionDataset;

class PredictionDatasetResource extends JsonResource
{
    public $include_stats = true;

    public function ignoreStats() : self {
        $this->include_stats = false;
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
            'comment' => $this->comment,
            'token' => $this->token,
            'user' => UserResource::make($this->user),
            'temperature' => $this->temperature,
            'membrane' => PredictionMembraneResource::make($this->membrane),
            'method' => PredictionDataset::method($this->method_type),
            'priority' => $this->priority,
            'stats' => [
                'pending' => $this->pending,
                'running' => $this->running,
                'done' => $this->done,
                'failed' => $this->failed,
                'total' => $this->total
            ],
            'overall_stats' => [
                'pending' => 0,
                'running' => 0,
                'done' => 0
            ],
            'updated_at' => $this->updated_at->format('Y/m/d H:i:s'),
            'created_at' => $this->created_at->format('Y/m/d H:i:s'),
            'predictions' => PredictionResource::collection($this->whenLoaded('predictions')),
        ];
    }
}
