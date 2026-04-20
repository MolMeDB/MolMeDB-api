<?php

namespace App\Jobs;

use App\Enums\UploadQueueLogContextEnums;
use App\Enums\UploadQueueLogTypeEnums;
use App\Models\UploadQueue;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessUploadQueueRecord implements ShouldBeUnique, ShouldQueue
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

    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->record_id)
                ->releaseAfter(60)
                ->expireAfter(3600),
        ];
    }

    /**
     * Create the event listener.
     */
    public function __construct(protected int $record_id, protected ?User $user = null)
    {
        // Save log - upload started
        $record = UploadQueue::find($this->record_id);

        if ($record) {
            $record->addStructuredLog(
                'Upload run has been queued.',
                UploadQueueLogContextEnums::INFO,
                UploadQueueLogTypeEnums::UPLOAD_RUN,
                $record->state,
                null,
                $user?->id
            );
        }
    }

    /**
     * Handle the event.
     */
    public function handle(): void
    {
        $record = UploadQueue::find($this->record_id);

        try {
            Log::channel('upload')
                ->info('Starting upload', [
                    $this->record_id,
                    $record?->file?->path,
                ]);

            if (! $record) {
                Log::channel('upload')
                    ->error('Upload queue record not found', [
                        $this->record_id,
                    ]);

                return;
            }

            if ($record->state !== UploadQueue::STATE_PENDING) {
                Log::channel('upload')
                    ->warning('Upload queue record has invalid state. Stopping...', [
                        $this->record_id,
                        $record->enumState($record->state),
                    ]);

                return;
            }

            $record->transitionToState(
                UploadQueue::STATE_RUNNING,
                'Upload processing has started.',
                UploadQueueLogContextEnums::INFO,
                UploadQueueLogTypeEnums::UPLOAD_RUN,
                null,
                $record->user_id
            );

            $disk = $record->file?->storage;
            $path = $record->file?->path;

            if (! is_string($disk) || trim($disk) === '') {
                throw new Exception('Uploaded file storage is not configured.');
            }

            if (! is_string($path) || trim($path) === '' || ! Storage::disk($disk)->exists($path)) {
                throw new Exception('Uploaded file is missing on storage.');
            }

            // Placeholder for the final import pipeline.
            // At this stage the record is considered ready and accepted by processing.
            $record->transitionToState(
                UploadQueue::STATE_DONE,
                'Upload processing has finished successfully.',
                UploadQueueLogContextEnums::SUCCESS,
                UploadQueueLogTypeEnums::UPLOAD_RUN,
                null,
                $record->user_id
            );

            Log::channel('upload')
                ->info('Upload finished', [
                    $this->record_id,
                    $record->file?->path,
                ]);
        } catch (Exception $e) {
            Log::channel('upload')
                ->error('Exception thrown during execution', [
                    $this->record_id,
                    $e,
                ]);

            if ($record) {
                $record->transitionToState(
                    UploadQueue::STATE_ERROR,
                    $e->getMessage(),
                    UploadQueueLogContextEnums::ERROR,
                    UploadQueueLogTypeEnums::UPLOAD_RUN,
                    null,
                    $record->user_id
                );
            }
        }
    }
}
