<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    public const KEY_FEEDBACK_ACCEPTED = 'feedback.accepted';

    public const KEY_UPLOAD_JOB_FINISHED = 'upload.job.finished';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public static function keyOptions(): array
    {
        return [
            self::KEY_FEEDBACK_ACCEPTED => 'Feedback accepted',
            self::KEY_UPLOAD_JOB_FINISHED => 'Upload job finished',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function availableKeyOptions(?self $currentTemplate = null): array
    {
        $usedKeys = self::query()
            ->when($currentTemplate, fn ($query) => $query->whereKeyNot($currentTemplate->getKey()))
            ->pluck('key')
            ->all();

        return collect(self::keyOptions())
            ->reject(fn (string $label, string $key): bool => in_array($key, $usedKeys, true))
            ->all();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }
}
