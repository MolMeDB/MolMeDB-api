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
            'total_steps' => Prediction::finalStep(),
            'enum_step' => Prediction::enumStep($this->step),
            'progress_percent' => Prediction::progressPercent($this->step, $this->state, $this->result_id),
            'temperature' => $this->temperature,
            'membrane' => PredictionMembraneResource::make($this->predictionMembrane),
            'method_type' => $this->method_type,
            'method' => Prediction::enumMethod($this->method_type),
            'remote_method' => $this->remote_method,
            'remote_calculation_id' => $this->remote_calculation_id,
            'remote_molecule_id' => $this->remote_molecule_id,
            'remote_status' => $this->remote_status,
            'remote_paused_at' => $this->remote_paused_at?->format('Y/m/d H:i:s'),
            'remote_pause_reason' => $this->remote_pause_reason,
            'enum_remote_status' => Prediction::enumRemoteStatus($this->remote_status),
            'remote_current_step' => $this->remote_current_step,
            'remote_heartbeat_at' => $this->remote_heartbeat_at?->format('Y/m/d H:i:s'),
            'remote_last_status_at' => $this->remote_last_status_at?->format('Y/m/d H:i:s'),
            'remote_finished_at' => $this->remote_finished_at?->format('Y/m/d H:i:s'),
            'remote_error_message' => $this->remote_error_message,
            'logs' => $this->logs ?? [],
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
