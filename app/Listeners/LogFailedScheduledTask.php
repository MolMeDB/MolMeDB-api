<?php

namespace App\Listeners;

use App\Enums\PermissionEnums;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use App\Services\SystemActivityLogger;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Support\Str;

class LogFailedScheduledTask
{
    public function __construct(
        private readonly SystemActivityLogger $logger,
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(ScheduledTaskFailed $event): void
    {
        $task = $event->task->getSummaryForDisplay();
        $error = Str::limit($event->exception->getMessage(), 1000, '...');

        $this->logger->logThrottled(
            event: 'scheduled_task_failed',
            description: 'Scheduled task failed: '.$task.'.',
            properties: [
                'task' => $task,
                'exit_code' => $event->task->exitCode,
                'exception' => $event->exception::class,
                'error' => $error,
            ],
            throttleKey: $task,
        );

        $this->notificationService->sendToPermission(
            PermissionEnums::SYSTEM_MONITOR->value,
            NotificationTemplate::KEY_SYSTEM_ADMIN_JOB_FAILED,
            [
                'label' => 'Scheduled task: '.$task,
                'error' => $error,
            ],
        );
    }
}
