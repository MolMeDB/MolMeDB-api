<?php

namespace App\Http\Resources;

use App\Models\UploadQueue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadQueueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastLog = $this->logs?->last();

        return [
            'id' => $this->id,
            'state' => $this->state,
            'state_label' => UploadQueue::$ui_enum_states[$this->state] ?? UploadQueue::enumState($this->state),
            'state_phase' => UploadQueue::enumState($this->state),
            'can_reupload' => $this->canBeReuploaded(),
            'can_configure' => $this->canBeConfigured(),
            'can_enqueue' => $this->canBeEnqueued(),
            'can_revert' => $this->canBeRevertedToConfigState(),
            'can_cancel' => $this->canBeCanceled(),
            'dataset' => DatasetResource::make($this->dataset),
            'secondary_publication' => PublicationResource::make($this->publication),
            'file' => FileResource::make($this->file),
            'last_message' => $lastLog?->message,
            'config' => $this->config->toFrontendArray(),
            'logs' => $this->logs->map(fn ($log) => [
                'message' => $log->message,
                'context' => $log->context->value,
                'type' => $log->type->value,
                'state' => $log->state,
                'payload' => $log->payload,
                'timestamp' => $log->timestamp,
                'user_id' => $log->user_id,
            ])->values(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
