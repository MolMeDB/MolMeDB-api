<?php

namespace App\Console\Commands;

use App\Enums\UploadQueueLogContextEnums;
use App\Enums\UploadQueueLogTypeEnums;
use App\Jobs\ProcessUploadQueueRecord;
use App\Models\UploadQueue;
use App\Services\UploadQueueDetailedValidator;
use Illuminate\Console\Command;
use Throwable;

class ProcessFrontendUploads extends Command
{
    protected $signature = 'uploads:process-frontend {--limit=25}';

    protected $description = 'Runs detailed validation for frontend uploads and enqueues valid records for processing.';

    public function handle(UploadQueueDetailedValidator $validator): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $queuedCount = 0;
        $errorCount = 0;

        $pendingRecords = UploadQueue::query()
            ->with('user')
            ->where('state', UploadQueue::STATE_PENDING)
            ->whereRaw("COALESCE((config->>'detailed_validation_ok')::boolean, false) = false")
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($pendingRecords as $record) {
            try {
                $record->addStructuredLog(
                    'Detailed validation started.',
                    UploadQueueLogContextEnums::INFO,
                    UploadQueueLogTypeEnums::VALIDATION_RUN,
                    $record->state,
                    null,
                    $record->user_id
                );

                $result = $validator->validate($record);
                if (! ($result['ok'] ?? false)) {
                    $record->transitionToState(
                        UploadQueue::STATE_ERROR,
                        'Detailed validation failed.',
                        UploadQueueLogContextEnums::ERROR,
                        UploadQueueLogTypeEnums::VALIDATION_RUN,
                        ['errors' => $result['errors'] ?? []],
                        $record->user_id
                    );
                    $errorCount++;

                    continue;
                }

                $record->config = $record->config
                    ->merge($result['config'] ?? [])
                    ->withDetailedValidation(
                        true,
                        $result['config']['validated_rows'] ?? null,
                        $result['config']['validated_at'] ?? null,
                        now()->toISOString(),
                    );
                $record->save();

                $record->addStructuredLog(
                    'Detailed validation passed. Record stays pending and is ready for processing.',
                    UploadQueueLogContextEnums::SUCCESS,
                    UploadQueueLogTypeEnums::VALIDATION_RUN,
                    $record->state,
                    ['validated_rows' => $result['config']['validated_rows'] ?? null],
                    $record->user_id
                );

                ProcessUploadQueueRecord::dispatch($record->id, $record->user);
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
