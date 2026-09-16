<?php

namespace App\Jobs;

use App\Enums\UploadQueueLogContextEnums;
use App\Enums\UploadQueueLogTypeEnums;
use App\Models\NotificationTemplate;
use App\Models\UploadQueue;
use App\Services\NotificationService;
use App\Services\UploadQueueLogEmailFormatter;
use App\ValueObjects\UploadQueueLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendUploadQueueStatusUpdate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const WAIT_SECONDS = 600;

    public int $tries = 12;

    public int $timeout = 120;

    public int|array $backoff = [15, 30, 60];

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->recordId))
                ->releaseAfter(15)
                ->expireAfter(180),
        ];
    }

    public function __construct(
        private readonly int $recordId,
        private readonly bool $sendImmediately = false,
    ) {
        $this->onQueue('default');
    }

    public function handle(
        NotificationService $notificationService,
        UploadQueueLogEmailFormatter $logFormatter,
    ): void {
        $record = UploadQueue::query()
            ->with(['user', 'dataset'])
            ->find($this->recordId);

        if (! $record) {
            return;
        }

        $pending = $record->unnotifiedLogs();

        if ($pending->isEmpty()) {
            return;
        }

        $lastPending = $pending->last();

        if (! $this->shouldSendNow($record, $lastPending)) {
            $seconds = $this->secondsUntilDigest($lastPending->timestamp);

            if ($seconds > 0) {
                $this->release($seconds);

                return;
            }
        }

        $email = $record->user?->email ?? $record->guest_email;

        if (! $email) {
            return;
        }

        $sent = $notificationService->sendEmailOnly(
            $email,
            NotificationTemplate::KEY_UPLOAD_STATUS_UPDATE,
            [
                'record_id' => $record->id,
                'dataset_name' => $record->dataset?->name ?? '',
                'state_label' => UploadQueue::$ui_enum_states[$record->state] ?? '',
                'manage_url' => $record->trackingUrl(),
                'logs' => $logFormatter->format($pending),
            ],
        );

        if (! $sent) {
            return;
        }

        $record->addStructuredLog(
            'Status update digest sent to uploader.',
            UploadQueueLogContextEnums::INFO,
            UploadQueueLogTypeEnums::NOTIFICATION,
            $record->state,
            ['emailed_to' => $email, 'log_count' => $pending->count()],
            null,
        );
    }

    private function shouldSendNow(UploadQueue $record, UploadQueueLog $lastPending): bool
    {
        if ($this->sendImmediately) {
            return true;
        }

        if (in_array((int) $record->state, [
            UploadQueue::STATE_DONE,
            UploadQueue::STATE_REVIEW_REQUIRED,
        ], true)) {
            return true;
        }

        return $lastPending->context === UploadQueueLogContextEnums::ERROR;
    }

    private function secondsUntilDigest(?string $timestamp): int
    {
        if (! $timestamp) {
            return 0;
        }

        $sendAt = Carbon::parse($timestamp)->addSeconds(self::WAIT_SECONDS);

        return (int) max(0, now()->diffInSeconds($sendAt, false));
    }

    public function failed(?Throwable $throwable): void
    {
        Log::channel('upload')->error('Upload status notification job failed.', [
            'record_id' => $this->recordId,
            'error' => $throwable?->getMessage(),
        ]);
    }
}
