<?php

namespace App\Listeners;

use App\Enums\PermissionEnums;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use App\Services\SystemActivityLogger;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Str;

class LogFailedQueueJob
{
    public function __construct(
        private readonly SystemActivityLogger $logger,
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(JobFailed $event): void
    {
        $jobName = $event->job->resolveName();
        $error = Str::limit($event->exception->getMessage(), 1000, '...');

        $this->logger->logThrottled(
            event: 'queue_job_failed',
            description: 'Queue job failed: '.class_basename($jobName).'.',
            properties: [
                'job' => $jobName,
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'job_uuid' => $event->job->uuid(),
                'exception' => $event->exception::class,
                'error' => $error,
            ],
            throttleKey: $jobName,
        );

        $this->notificationService->sendToPermission(
            PermissionEnums::SYSTEM_MONITOR->value,
            NotificationTemplate::KEY_SYSTEM_ADMIN_JOB_FAILED,
            [
                'label' => 'Queue job: '.class_basename($jobName),
                'error' => $error,
            ],
        );
    }
}
