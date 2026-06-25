<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    public const KEY_FEEDBACK_ACCEPTED = 'feedback.accepted';

    public const KEY_UPLOAD_JOB_FINISHED = 'upload.job.finished';

    public const KEY_UPLOAD_RECEIVED = 'upload.received';

    public const KEY_UPLOAD_STATUS_UPDATE = 'upload.status_update';

    public const KEY_UPLOAD_ADMIN_NEW_SUBMISSION = 'upload.admin.new_submission';

    public const KEY_UPLOAD_ADMIN_REVIEW_REQUIRED = 'upload.admin.review_required';

    public const KEY_UPLOAD_ADMIN_PROCESSING_ERROR = 'upload.admin.processing_error';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public static function keyOptions(): array
    {
        return [
            self::KEY_FEEDBACK_ACCEPTED => 'Feedback accepted',
            self::KEY_UPLOAD_JOB_FINISHED => 'Upload job finished',
            self::KEY_UPLOAD_RECEIVED => 'Upload received (sent to uploader)',
            self::KEY_UPLOAD_STATUS_UPDATE => 'Upload status update (sent to uploader)',
            self::KEY_UPLOAD_ADMIN_NEW_SUBMISSION => 'Upload admin alert: new submission',
            self::KEY_UPLOAD_ADMIN_REVIEW_REQUIRED => 'Upload admin alert: review required',
            self::KEY_UPLOAD_ADMIN_PROCESSING_ERROR => 'Upload admin alert: processing error',
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
