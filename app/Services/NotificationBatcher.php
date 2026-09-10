<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\QueuedNotification;
use App\Models\User;

/**
 * Queues a single notification event instead of sending it right away, so a
 * burst of same-type events for the same recipient (e.g. several uploads
 * failing validation within the same minute) can be flushed as one message
 * instead of flooding the recipient with one per event.
 *
 * Reuses the generic QueuedNotification table (originally introduced for the
 * lab-upload admin digest) — see FlushQueuedNotifications for the draining
 * side, which reads NotificationType::batchWindowMinutes() to decide when a
 * group is ready to send.
 */
class NotificationBatcher
{
    /**
     * @param  array<string, mixed>  $data  the same payload that would be
     *                                      passed to NotificationService::send() for this event
     */
    public function queue(User $recipient, NotificationType $type, array $data): void
    {
        QueuedNotification::query()->create([
            'group_key' => self::groupKey($type),
            'event' => $type->value,
            'notifiable_type' => $recipient->getMorphClass(),
            'notifiable_id' => $recipient->getKey(),
            'data' => $data,
        ]);
    }

    public static function groupKey(NotificationType $type): string
    {
        return 'notification_batch:'.$type->value;
    }
}
