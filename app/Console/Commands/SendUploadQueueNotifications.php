<?php

namespace App\Console\Commands;

use App\Enums\UploadQueueLogContextEnums;
use App\Enums\UploadQueueLogTypeEnums;
use App\Jobs\SendUploadQueueStatusUpdate;
use App\Models\UploadQueue;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendUploadQueueNotifications extends Command
{
    protected $signature = 'lab-upload:send-notifications';

    protected $description = 'Queues "still waiting" reminders and throttled status-update digests for lab upload records.';

    private const STALE_REMINDER_AFTER_HOURS = 24;

    private const DIGEST_MIN_AGE_MINUTES = 10;

    public function handle(): int
    {
        $remindersInjected = $this->injectStaleReminders();
        $digestsQueued = $this->queuePendingNotifications();

        $this->info("Reminders injected: {$remindersInjected}; digests queued: {$digestsQueued}.");

        return self::SUCCESS;
    }

    private function injectStaleReminders(): int
    {
        $threshold = now()->subHours(self::STALE_REMINDER_AFTER_HOURS);
        $count = 0;

        UploadQueue::query()
            ->whereIn('state', [UploadQueue::STATE_UPLOADED, UploadQueue::STATE_CONFIGURED])
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($threshold, &$count): void {
                foreach ($records as $record) {
                    $lastLog = $record->logs->last();

                    if (! $lastLog || ! $lastLog->timestamp) {
                        continue;
                    }

                    if (Carbon::parse($lastLog->timestamp)->gt($threshold)) {
                        continue;
                    }

                    $message = (int) $record->state === UploadQueue::STATE_UPLOADED
                        ? 'Your uploaded data is still waiting for configuration.'
                        : 'Your configured upload is still waiting to be started.';

                    $record->addStructuredLog(
                        $message,
                        UploadQueueLogContextEnums::WARNING,
                        UploadQueueLogTypeEnums::STATE_CHANGE,
                        $record->state,
                        null,
                        null,
                    );

                    $count++;
                }
            });

        return $count;
    }

    private function queuePendingNotifications(): int
    {
        $threshold = now()->subMinutes(self::DIGEST_MIN_AGE_MINUTES);
        $queued = 0;

        UploadQueue::query()
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($threshold, &$queued): void {
                foreach ($records as $record) {
                    $pending = $record->unnotifiedLogs();

                    if ($pending->isEmpty()) {
                        continue;
                    }

                    $lastPending = $pending->last();
                    $isError = $lastPending->context === UploadQueueLogContextEnums::ERROR;
                    $isStale = $lastPending->timestamp !== null
                        && Carbon::parse($lastPending->timestamp)->lte($threshold);

                    if (! $isError && ! $isStale) {
                        continue;
                    }

                    if (! ($record->user_id || $record->guest_email)) {
                        continue;
                    }

                    SendUploadQueueStatusUpdate::dispatch($record->id, $isError);
                    $queued++;
                }
            });

        return $queued;
    }
}
