<?php

namespace App\Observers;

use App\Jobs\ProcessUploadQueueRecord;
use App\Models\UploadQueue;
use Illuminate\Support\Facades\Log;

class UploadQueueObserver
{
    public function updated(UploadQueue $uploadQueue): void
    {
        if (! $uploadQueue->wasChanged('state')) {
            return;
        }

        if ((int) $uploadQueue->state !== UploadQueue::STATE_PENDING) {
            return;
        }

        Log::channel('upload')->info('Dispatching upload processing job from UploadQueue observer.', [
            'record_id' => $uploadQueue->id,
            'previous_state' => $uploadQueue->getOriginal('state'),
            'state' => $uploadQueue->state,
        ]);

        ProcessUploadQueueRecord::dispatch($uploadQueue->id)->afterCommit();
    }
}
