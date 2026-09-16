<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackSubmission extends Model
{
    public const STATE_NEW = 'new';

    public const STATE_READ = 'read';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'read_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function states(): array
    {
        return [
            self::STATE_NEW => 'New',
            self::STATE_READ => 'Read',
        ];
    }

    public function markAsRead(): void
    {
        if ($this->state === self::STATE_READ) {
            return;
        }

        $this->forceFill([
            'state' => self::STATE_READ,
            'read_at' => now(),
        ])->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emailVerification(): BelongsTo
    {
        return $this->belongsTo(FeedbackEmailVerification::class, 'feedback_email_verification_id');
    }
}
