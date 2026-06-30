<?php

namespace App\Listeners;

use App\Services\SystemActivityLogger;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Str;

class LogFailedQueueJob
{
    public function __construct(private readonly SystemActivityLogger $logger) {}

    public function handle(JobFailed $event): void
    {
        $jobName = $event->job->resolveName();

        $this->logger->logThrottled(
            event: 'queue_job_failed',
            description: 'Queue job failed: '.class_basename($jobName).'.',
            properties: [
                'job' => $jobName,
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'job_uuid' => $event->job->uuid(),
                'exception' => $event->exception::class,
                'error' => Str::limit($event->exception->getMessage(), 1000, '...'),
            ],
            throttleKey: $jobName,
        );
    }
}
