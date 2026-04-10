<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PredictionWorkers\Models\Prediction;

class PredictionResource extends JsonResource
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
            'result' => PredictionResultResource::make($this->predictionResult)->withParsedResults($this->parseResults),
            'structure' => PredictionStructureResource::make($this->predictionStructure),
            'state' => $this->state,
            'enum_state' => Prediction::enumState($this->state),
            'step' => $this->step,
            'total_steps' => max(array_keys(Prediction::$enum_steps)),
            'enum_step' => Prediction::enumStep($this->step),
            'temperature' => $this->temperature,
            'membrane' => PredictionMembraneResource::make($this->predictionMembrane),
            'method' => Prediction::enumMethod($this->method_type),
            'priority' => $this->priority,
            'created_at' => $this->created_at->format('Y/m/d H:i:s'),
            'updated_at' => $this->updated_at->format('Y/m/d H:i:s'),
        ];
    }

    public static function collectionWithParsedResults($resource)
    {
        $collection = self::collection($resource);

        $collection->each(function ($item) {
            $item->withParsedResults(true);
        });

        return $collection;
    }
}
