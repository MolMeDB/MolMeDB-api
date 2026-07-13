<?php

namespace App\Services;

use App\Models\QueuedNotification;
use App\Models\UploadQueue;

class LabUploadAdminDigestQueue
{
    public const GROUP = 'lab_upload_admin_digest';

    public const EVENT_NEW_SUBMISSION = 'new_submission';

    public const EVENT_REVIEW_REQUIRED = 'review_required';

    public const EVENT_PROCESSING_ERROR = 'processing_error';

    /** @var array<string, string> */
    public static array $eventLabels = [
        self::EVENT_NEW_SUBMISSION => 'New submissions',
        self::EVENT_REVIEW_REQUIRED => 'Waiting for validation',
        self::EVENT_PROCESSING_ERROR => 'Processing errors',
    ];

    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator) {}

    public function push(string $event, UploadQueue $record, ?string $message = null): void
    {
        QueuedNotification::query()->create([
            'group_key' => self::GROUP,
            'event' => $event,
            'notifiable_type' => $record->getMorphClass(),
            'notifiable_id' => $record->getKey(),
            'data' => [
                'dataset_name' => $record->dataset?->name ?? '',
                'uploader_label' => $record->user?->email ?? $record->guest_email ?? 'guest',
                'admin_url' => $this->adminUrlGenerator->uploadQueueEditUrl($record),
                'message' => $message,
            ],
        ]);
    }
}
