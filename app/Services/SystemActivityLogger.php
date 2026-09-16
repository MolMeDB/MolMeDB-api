<?php

namespace App\Services;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SystemActivityLogger
{
    public const SEVERITY_INFO = 'info';

    public const SEVERITY_ERROR = 'error';

    public const DEFAULT_THROTTLE_SECONDS = 900;

    /** @param array<string, mixed> $properties */
    public function log(
        string $event,
        string $description,
        array $properties = [],
        string $severity = self::SEVERITY_INFO,
    ): bool {
        try {
            activity(BaseModel::ACTIVITY_LOG_SYSTEM)
                ->event($event)
                ->withProperties(['severity' => $severity, ...$properties])
                ->log(Str::limit($description, 1000, '...'));

            return true;
        } catch (Throwable $throwable) {
            Log::error('System activity could not be recorded.', [
                'event' => $event,
                'error' => $throwable->getMessage(),
            ]);

            return false;
        }
    }

    /** @param array<string, mixed> $properties */
    public function logThrottled(
        string $event,
        string $description,
        array $properties = [],
        string $severity = self::SEVERITY_ERROR,
        ?string $throttleKey = null,
        int $seconds = self::DEFAULT_THROTTLE_SECONDS,
    ): bool {
        $cacheKey = 'system-activity:'.sha1($event.'|'.($throttleKey ?? 'default'));

        try {
            if (! Cache::add($cacheKey, true, max(1, $seconds))) {
                return false;
            }
        } catch (Throwable $throwable) {
            Log::warning('System activity throttling is unavailable.', [
                'event' => $event,
                'error' => $throwable->getMessage(),
            ]);
        }

        return $this->log($event, $description, $properties, $severity);
    }
}
