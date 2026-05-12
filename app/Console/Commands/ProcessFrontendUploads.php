<?php

namespace App\Console\Commands;

use App\Enums\UploadQueueLogContextEnums;
use App\Enums\UploadQueueLogTypeEnums;
use App\Jobs\ProcessUploadQueueRecord;
use App\Models\UploadQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessFrontendUploads extends Command
{
    protected $signature = 'uploads:process-frontend {--limit=25}';

    protected $description = 'Dispatches pending frontend upload records to the upload queue.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $queuedCount = 0;
        $errorCount = 0;

        $pendingRecords = UploadQueue::query()
            ->with('user')
            ->where('state', UploadQueue::STATE_PENDING)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($pendingRecords as $record) {
            try {
                Log::channel('upload')->info('Dispatching pending upload processing job from command.', [
                    'record_id' => $record->id,
                ]);

                ProcessUploadQueueRecord::dispatch($record->id);
                $queuedCount++;
            } catch (Throwable $throwable) {
                $record->transitionToState(
                    UploadQueue::STATE_ERROR,
                    'Failed to enqueue record: '.$throwable->getMessage(),
                    UploadQueueLogContextEnums::ERROR,
                    UploadQueueLogTypeEnums::STATE_CHANGE,
                    null,
                    $record->user_id
                );
                $errorCount++;
            }
        }

        $this->info("Queued: {$queuedCount}; errors: {$errorCount}");

        return self::SUCCESS;
    }
}
