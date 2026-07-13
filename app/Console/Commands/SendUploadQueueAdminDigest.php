<?php

namespace App\Console\Commands;

use App\Models\Config;
use App\Models\NotificationTemplate;
use App\Models\QueuedNotification;
use App\Services\LabUploadAdminDigestQueue;
use App\Services\NotificationService;
use App\Services\SystemActivityLogger;
use App\Services\UploadQueueAdminDigestFormatter;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendUploadQueueAdminDigest extends Command
{
    protected $signature = 'lab-upload:send-admin-digest';

    protected $description = 'Sends a throttled (max once every 2 hours) digest email to admins summarizing pending lab upload events.';

    private const THROTTLE_HOURS = 2;

    public function handle(
        NotificationService $notificationService,
        UploadQueueAdminDigestFormatter $formatter,
        SystemActivityLogger $activityLogger,
    ): int {
        $pending = QueuedNotification::query()
            ->forGroup(LabUploadAdminDigestQueue::GROUP)
            ->pending()
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        $lastSentAt = Config::get(Config::KEY_LAB_UPLOAD_ADMIN_DIGEST_LAST_SENT_AT);

        if ($lastSentAt && Carbon::parse($lastSentAt)->addHours(self::THROTTLE_HOURS)->isFuture()) {
            return self::SUCCESS;
        }

        $email = trim((string) Config::get(Config::KEY_LAB_UPLOAD_ADMIN_EMAIL_FALLBACK, ''));

        if (! filled($email)) {
            return self::SUCCESS;
        }

        $sent = $notificationService->sendEmailOnly($email, NotificationTemplate::KEY_UPLOAD_ADMIN_DIGEST, [
            'count' => $pending->count(),
            'summary' => $formatter->format($pending),
        ]);

        if (! $sent) {
            return self::SUCCESS;
        }

        QueuedNotification::query()
            ->whereIn('id', $pending->pluck('id'))
            ->update(['notified_at' => now()]);

        Config::set(Config::KEY_LAB_UPLOAD_ADMIN_DIGEST_LAST_SENT_AT, now()->toISOString());

        $this->info("Sent admin upload digest with {$pending->count()} pending event(s).");

        $activityLogger->log(
            event: 'upload_admin_digest_sent',
            description: "Lab upload admin digest sent with {$pending->count()} pending event(s).",
            properties: ['count' => $pending->count()],
        );

        return self::SUCCESS;
    }
}
