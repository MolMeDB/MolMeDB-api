<?php

namespace App\Services;

use App\Enums\UploadQueueLogContextEnums;
use App\ValueObjects\UploadQueueLog;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class UploadQueueLogEmailFormatter
{
    /**
     * @param  iterable<int, UploadQueueLog>  $logs
     */
    public function format(iterable $logs): HtmlString
    {
        $items = [];

        foreach (collect($logs)->sortByDesc(fn (UploadQueueLog $log): int => $this->timestampValue($log))->values() as $log) {
            $items[] = $this->formatLog($log);
        }

        return new HtmlString(
            '<div style="margin-top: 8px;">'.implode('', $items).'</div>'
        );
    }

    private function timestampValue(UploadQueueLog $log): int
    {
        if (! $log->timestamp) {
            return 0;
        }

        return Carbon::parse($log->timestamp)->getTimestamp();
    }

    private function formatLog(UploadQueueLog $log): string
    {
        [$background, $border, $label] = $this->tone($log->context);
        $timestamp = $log->timestamp
            ? Carbon::parse($log->timestamp)->format('Y-m-d H:i')
            : '?';

        return sprintf(
            '<div style="margin: 0 0 12px 0; padding: 10px 12px; border-left: 4px solid %s; background: %s; border-radius: 6px;">%s%s</div>',
            $border,
            $background,
            sprintf(
                '<div style="margin-bottom: 6px; font-size: 12px; font-weight: 700; color: %s;">%s · %s · %s</div>',
                $border,
                e($label),
                e($log->type->value),
                e($timestamp),
            ),
            '<div style="white-space: pre-wrap; line-height: 1.55; color: #374151;">'.e($log->message).'</div>',
        );
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function tone(UploadQueueLogContextEnums $context): array
    {
        return match ($context) {
            UploadQueueLogContextEnums::ERROR => ['#fef2f2', '#dc2626', 'Error'],
            UploadQueueLogContextEnums::WARNING => ['#fffbeb', '#d97706', 'Warning'],
            UploadQueueLogContextEnums::SUCCESS => ['#f0fdf4', '#16a34a', 'Success'],
            UploadQueueLogContextEnums::INFO => ['#eff6ff', '#2563eb', 'Info'],
        };
    }
}
