<?php

namespace App\Jobs;

use App\Enums\UploadQueueLogContextEnums;
use App\Enums\UploadQueueLogTypeEnums;
use App\Models\UploadQueue;
use App\Rules\UploadFile\Identifiers\ColumnChebi;
use App\Rules\UploadFile\Identifiers\ColumnChembl;
use App\Rules\UploadFile\Identifiers\ColumnPdb;
use App\Rules\UploadFile\Identifiers\ColumnPubchem;
use App\Services\External\Chemical\Unichem\Unichem;
use App\Services\UploadQueueDetailedValidator;
use App\Services\UploadQueueImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProcessUploadQueueRecord implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const UNICHEM_RETRY_DELAY_SECONDS = 1800;

    public int $tries = 48;

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    /**
     * Do not queue twice the same UploadQueue record
     */
    public function uniqueId(): string
    {
        return (string) $this->recordId;
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->recordId)
                ->releaseAfter(60)
                ->expireAfter(3600),
        ];
    }

    public function __construct(protected int $recordId)
    {
        $this->onQueue('upload');
        $this->delay(2);
    }

    /**
     * Handle the event.
     */
    public function handle(
        UploadQueueDetailedValidator $validator,
        UploadQueueImporter $importer,
    ): void {
        $record = UploadQueue::query()
            ->with(['file', 'dataset', 'user'])
            ->find($this->recordId);

        if (! $record) {
            Log::channel('upload')
                ->error('Upload queue record not found', [
                    'record_id' => $this->recordId,
                ]);

            return;
        }

        if (! in_array((int) $record->state, [UploadQueue::STATE_PENDING, UploadQueue::STATE_RUNNING], true)) {
            Log::channel('upload')
                ->warning('Upload queue record has invalid state. Stopping...', [
                    'record_id' => $this->recordId,
                    'state' => $record->enumState($record->state),
                ]);

            return;
        }

        if ((int) $record->state === UploadQueue::STATE_PENDING) {
            $record->transitionToState(
                UploadQueue::STATE_RUNNING,
                'Upload processing has started.',
                UploadQueueLogContextEnums::INFO,
                UploadQueueLogTypeEnums::UPLOAD_RUN,
                null,
                $record->user_id
            );
        }

        try {
            Log::channel('upload')
                ->info('Starting upload processing', [
                    'record_id' => $this->recordId,
                    'file_path' => $record->file?->path,
            ]);

            if (! $record->config->detailedValidationPassed()) {
                if ($this->shouldWaitForUnichem($record)) {
                    $this->waitForUnichem($record);

                    return;
                }

                $this->runDetailedValidation($record, $validator);
                $record->refresh()->load(['file', 'dataset', 'user']);
            }

            if (! $record->config->adminReviewApproved()) {
                $summary = $importer->summarize($record);

                $record->transitionToState(
                    UploadQueue::STATE_REVIEW_REQUIRED,
                    'Automatic validation passed. Upload data is waiting for administrator review.',
                    UploadQueueLogContextEnums::SUCCESS,
                    UploadQueueLogTypeEnums::UPLOAD_RUN,
                    $summary,
                    $record->user_id
                );

                Log::channel('upload')
                    ->info('Upload waiting for administrator review', $summary);

                return;
            }

            $summary = $importer->import($record);

            $record->transitionToState(
                UploadQueue::STATE_DONE,
                'All interactions successfully imported.',
                UploadQueueLogContextEnums::SUCCESS,
                UploadQueueLogTypeEnums::UPLOAD_RUN,
                $summary,
                $record->user_id
            );

            Log::channel('upload')
                ->info('Upload import preparation finished', $summary);
        } catch (Throwable $throwable) {
            $record->refresh();

            if ((int) $record->state !== UploadQueue::STATE_ERROR) {
                $record->transitionToState(
                    UploadQueue::STATE_ERROR,
                    $throwable->getMessage(),
                    UploadQueueLogContextEnums::ERROR,
                    UploadQueueLogTypeEnums::UPLOAD_RUN,
                    null,
                    $record->user_id
                );
            }

            throw $throwable;
        }
    }

    public function failed(?Throwable $throwable): void
    {
        $record = UploadQueue::query()->find($this->recordId);

        if ($record && (int) $record->state === UploadQueue::STATE_RUNNING) {
            $record->transitionToState(
                UploadQueue::STATE_ERROR,
                $throwable?->getMessage() ?? 'Upload processing failed.',
                UploadQueueLogContextEnums::ERROR,
                UploadQueueLogTypeEnums::UPLOAD_RUN,
                null,
                $record->user_id
            );
        }
    }

    private function runDetailedValidation(
        UploadQueue $record,
        UploadQueueDetailedValidator $validator,
    ): void {
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
            $errors = $result['errors'] ?? [];
            $message = $this->detailedValidationFailureMessage($errors);

            $record->transitionToState(
                UploadQueue::STATE_ERROR,
                $message,
                UploadQueueLogContextEnums::ERROR,
                UploadQueueLogTypeEnums::VALIDATION_RUN,
                ['errors' => $errors],
                $record->user_id
            );

            throw new RuntimeException($message);
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
            'Detailed validation passed. Record is ready for import.',
            UploadQueueLogContextEnums::SUCCESS,
            UploadQueueLogTypeEnums::VALIDATION_RUN,
            $record->state,
            ['validated_rows' => $result['config']['validated_rows'] ?? null],
            $record->user_id
        );
    }

    private function shouldWaitForUnichem(UploadQueue $record): bool
    {
        if (! $this->usesUnichemValidatedIdentifier($record)) {
            return false;
        }

        return ! (new Unichem)->is_reachable();
    }

    private function usesUnichemValidatedIdentifier(UploadQueue $record): bool
    {
        $unichemColumnKeys = [
            ColumnChebi::$key,
            ColumnChembl::$key,
            ColumnPdb::$key,
            ColumnPubchem::$key,
        ];

        foreach ($record->config->attributes() as $columnKey) {
            if (is_string($columnKey) && in_array($columnKey, $unichemColumnKeys, true)) {
                return true;
            }
        }

        return false;
    }

    private function waitForUnichem(UploadQueue $record): void
    {
        $record->addStructuredLog(
            'UniChem API is not reachable. Upload processing is waiting for UniChem availability and will be retried in 30 minutes.',
            UploadQueueLogContextEnums::WARNING,
            UploadQueueLogTypeEnums::VALIDATION_RUN,
            UploadQueue::STATE_RUNNING,
            [
                'service' => 'unichem',
                'retry_delay_seconds' => self::UNICHEM_RETRY_DELAY_SECONDS,
                'retry_at' => now()->addSeconds(self::UNICHEM_RETRY_DELAY_SECONDS)->toISOString(),
            ],
            $record->user_id
        );

        $this->release(self::UNICHEM_RETRY_DELAY_SECONDS);
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function detailedValidationFailureMessage(array $errors): string
    {
        if ($errors === []) {
            return 'Detailed validation failed.';
        }

        $visibleErrors = array_slice($errors, 0, 10);
        $message = "Detailed validation failed:\n- ".implode("\n- ", $visibleErrors);

        $remainingErrors = count($errors) - count($visibleErrors);
        if ($remainingErrors > 0) {
            $message .= "\n- ...and {$remainingErrors} more errors.";
        }

        return $message;
    }
}
