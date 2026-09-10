<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;

/**
 * Resolves whether a user is allowed to receive a given notification type at
 * all (permission gate), and whether a channel is enabled for them (their
 * own override, falling back to the type's default). Shared by
 * NotificationService (sending), the preferences API (listing/saving), and
 * the notification batcher/admin fan-out (deciding who to queue for).
 */
class NotificationPreferenceResolver
{
    public function isEligible(User $user, string $notificationKey): bool
    {
        $type = NotificationType::tryFrom($notificationKey);

        return $type === null || $type->isEligible($user);
    }

    public function emailEnabled(User $user, string $notificationKey): bool
    {
        $type = NotificationType::tryFrom($notificationKey);

        if ($type === null) {
            return true;
        }

        return $this->preference($user, $notificationKey)?->email_enabled ?? $type->defaultEmail();
    }

    public function pushEnabled(User $user, string $notificationKey): bool
    {
        $type = NotificationType::tryFrom($notificationKey);

        if ($type === null) {
            return false;
        }

        return $this->preference($user, $notificationKey)?->push_enabled ?? $type->defaultPush();
    }

    private function preference(User $user, string $notificationKey): ?NotificationPreference
    {
        return NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_key', $notificationKey)
            ->first();
    }
}
