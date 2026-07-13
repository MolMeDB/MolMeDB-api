<?php

namespace App\Services;

use App\Models\QueuedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class UploadQueueAdminDigestFormatter
{
    private const TONES = [
        LabUploadAdminDigestQueue::EVENT_NEW_SUBMISSION => ['#eff6ff', '#2563eb'],
        LabUploadAdminDigestQueue::EVENT_REVIEW_REQUIRED => ['#fffbeb', '#d97706'],
        LabUploadAdminDigestQueue::EVENT_PROCESSING_ERROR => ['#fef2f2', '#dc2626'],
    ];

    /**
     * @param  Collection<int, QueuedNotification>  $items
     */
    public function format(Collection $items): HtmlString
    {
        $sections = '';

        foreach (LabUploadAdminDigestQueue::$eventLabels as $event => $label) {
            $group = $items->where('event', $event)->values();

            if ($group->isEmpty()) {
                continue;
            }

            [$background, $border] = self::TONES[$event];

            $rows = $group
                ->map(fn (QueuedNotification $item): string => $this->formatItem($item, $background, $border))
                ->implode('');

            $sections .= sprintf(
                '<div style="margin: 0 0 16px 0;"><div style="font-size: 13px; font-weight: 700; color: %s; margin-bottom: 8px;">%s (%d)</div>%s</div>',
                $border,
                e($label),
                $group->count(),
                $rows,
            );
        }

        return new HtmlString($sections);
    }

    private function formatItem(QueuedNotification $item, string $background, string $border): string
    {
        $data = $item->data ?? [];
        $message = $data['message'] ?? null;

        $detail = filled($message)
            ? '<div style="margin-top: 4px; color: #374151;">'.e($message).'</div>'
            : '';

        return sprintf(
            '<div style="margin: 0 0 8px 0; padding: 8px 10px; border-left: 4px solid %s; background: %s; border-radius: 6px;"><a href="%s">%s</a> — %s%s</div>',
            $border,
            $background,
            e($data['admin_url'] ?? '#'),
            e($data['dataset_name'] ?: 'Upload #'.$item->notifiable_id),
            e($data['uploader_label'] ?? 'guest'),
            $detail,
        );
    }
}
