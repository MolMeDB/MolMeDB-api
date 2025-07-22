<?php

namespace App\Jobs;

use App\Enums\UploadQueueLogContextEnums;
use App\Models\UploadQueue;
use App\Models\User;
use App\ValueObjects\UploadQueueLog;
use Exception;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessUploadQueueRecord implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;

    /**
     * Name of the queue, where to place the job
     */
    public $queue = 'upload';

    /**
     * The time (seconds) before the job should be processed.
     */
    public $delay = 2;

    /**
     * Do not queue twice the same UploadQueue record
     */
    public function uniqueId()
    {
        return $this->record_id;
    }

    public function middleware() : array
    {
        return [
            new WithoutOverlapping($this->record_id)
                ->releaseAfter(60)
                ->expireAfter(3600)
        ];
    }

    /**
     * Create the event listener.
     */
    public function __construct(protected int $record_id, protected ?User $user = null){
        // Save log - upload started
        $record = UploadQueue::find($this->record_id);

        if($record)
        {
            $record->addLog(
                new UploadQueueLog(
                    'Queued/started', 
                    UploadQueueLogContextEnums::INFO, 
                    now(), 
                    $user?->id
                )
            );
        }
    }


    /**
     * Handle the event.
     */
    public function handle(): void
    {
        $record = UploadQueue::find($this->record_id);

        try
        {
            Log::channel('upload')
                ->info('Starting upload', [
                    $this->record_id,
                    $record?->file?->path
                ]);

            if(!$record)
            {
                Log::channel('upload')
                    ->error('Upload queue record not found', [
                        $this->record_id
                    ]);
                return;
            }

            throw new Exception('Test');

            if($record->state !== UploadQueue::STATE_PENDING)
            {
                Log::channel('upload')
                    ->warning('Upload queue record has invalid state. Stopping...', [
                        $this->record_id,
                        $record->enumState($record->state)
                    ]);
                return;
            }

            // Just for testing - return to previous state
            $record->state = UploadQueue::STATE_CONFIGURED;
            $record->save();

            Log::channel('upload')
                ->info('Upload finished', [
                    $this->record_id,
                    $record->file?->path
                ]);
        }
        catch (Exception $e)
        {
            Log::channel('upload')
                ->error('Exception thrown during execution', [
                    $this->record_id,
                    $e
                ]);

            $record->state = UploadQueue::STATE_ERROR;
            $record->addLog(
                new UploadQueueLog($e->getMessage(), UploadQueueLogContextEnums::ERROR, now())
            );
            $record->save();
        }
    }
}
