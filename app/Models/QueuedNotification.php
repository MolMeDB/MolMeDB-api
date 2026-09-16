<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Generic queue of pending notification events, meant to be drained by a
 * throttled digest command (e.g. "send at most once every N hours").
 *
 * Group different notification features by `group_key` and free-form
 * `event`/`data` so new throttled digests can reuse this table instead of
 * each needing its own.
 */
class QueuedNotification extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'notified_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('notified_at');
    }

    public function scopeForGroup(Builder $query, string $groupKey): Builder
    {
        return $query->where('group_key', $groupKey);
    }
}
