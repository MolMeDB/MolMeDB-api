<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single browser/device subscribed to receive web push notifications for
 * a user (the PushSubscription JS API allows more than one endpoint per
 * user — e.g. one per device/browser they're logged in on).
 */
class PushSubscription extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
