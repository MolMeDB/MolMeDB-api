<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user override of a notification type's default channels. A missing
 * row means "use NotificationType::defaultEmail()/defaultPush()" — rows are
 * only written when a user actually changes something away from the
 * default, so adding a new NotificationType later needs no backfill.
 */
class NotificationPreference extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'push_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): ?NotificationType
    {
        return NotificationType::tryFrom($this->notification_key);
    }
}
