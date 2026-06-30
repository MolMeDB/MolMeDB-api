<?php

namespace App\Listeners;

use App\Services\SystemActivityLogger;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Support\Str;

class LogFailedScheduledTask
{
    public function __construct(private readonly SystemActivityLogger $logger) {}

    public function handle(ScheduledTaskFailed $event): void
    {
        $task = $event->task->getSummaryForDisplay();

        $this->logger->logThrottled(
            event: 'scheduled_task_failed',
            description: 'Scheduled task failed: '.$task.'.',
            properties: [
                'task' => $task,
                'exit_code' => $event->task->exitCode,
                'exception' => $event->exception::class,
                'error' => Str::limit($event->exception->getMessage(), 1000, '...'),
            ],
            throttleKey: $task,
        );
    }
}
