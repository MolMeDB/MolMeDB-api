<?php

namespace App\Services;

use App\Models\BaseModel;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\TemplatedNotification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NotificationService
{
    public const EVENT_TEMPLATE_RENDER_FAILED = 'notification_template_render_failed';

    public const EVENT_EMAIL_FAILED = 'notification_email_failed';

    /**
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string|NotificationTemplate $template, array $data = []): ?UserNotification
    {
        $template = $this->resolveTemplate($template);

        if (! $template) {
            return null;
        }

        $notification = $this->buildNotification($template, $data);

        if (! $notification) {
            return null;
        }

        return DB::transaction(function () use ($user, $template, $notification): UserNotification {
            $payload = $notification->toUserNotificationData();

            $record = UserNotification::query()->create([
                'user_id' => $user->id,
                'notification_template_id' => $template->id,
                'state' => UserNotification::STATE_NEW,
                'title' => $payload['title'],
                'body' => $payload['body'],
                'email_subject' => $payload['email_subject'],
                'email_message' => $payload['email_message'],
                'data' => $payload['data'],
            ]);

            if ($payload['email_subject'] && $payload['email_message']) {
                try {
                    $user->notify($notification);

                    $record->forceFill([
                        'emailed_at' => now(),
                    ])->save();
                } catch (Throwable $exception) {
                    $this->logEmailFailure(
                        description: $exception->getMessage(),
                        templateKey: $template->key,
                        properties: [
                            'user_id' => $user->id,
                            'user_notification_id' => $record->id,
                        ],
                    );
                }
            }

            return $record;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendByName(User $user, string $templateName, array $data = []): ?UserNotification
    {
        $template = NotificationTemplate::query()
            ->where('name', $templateName)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return null;
        }

        return $this->send($user, $template, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendByAction(User $user, string $action, array $data = []): ?UserNotification
    {
        return $this->send($user, $action, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendEmailOnly(string $email, string|NotificationTemplate $template, array $data = []): bool
    {
        $template = $this->resolveTemplate($template);

        if (! $template) {
            return false;
        }

        $notification = $this->buildNotification($template, $data);

        if (! $notification) {
            return false;
        }

        $payload = $notification->toUserNotificationData();

        if (! $payload['email_subject'] || ! $payload['email_message']) {
            return false;
        }

        try {
            Notification::route('mail', $email)->notify($notification);

            return true;
        } catch (Throwable $exception) {
            $this->logEmailFailure(
                description: $exception->getMessage(),
                templateKey: $template->key,
                properties: [
                    'email' => $email,
                ],
            );

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendEmailByAction(string $email, string $action, array $data = []): bool
    {
        return $this->sendEmailOnly($email, $action, $data);
    }

    private function resolveTemplate(string|NotificationTemplate $template): ?NotificationTemplate
    {
        if ($template instanceof NotificationTemplate) {
            return $template->is_active ? $template : null;
        }

        return NotificationTemplate::query()
            ->where('key', $template)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildNotification(NotificationTemplate $template, array $data): ?TemplatedNotification
    {
        try {
            return new TemplatedNotification(
                notificationTitle: $this->renderString($template->notification_title, $data),
                notificationBody: $this->renderString($template->notification_body, $data),
                emailSubject: filled($template->email_subject)
                    ? $this->renderString($template->email_subject, $data)
                    : null,
                emailMessage: filled($template->email_message)
                    ? $this->renderString($template->email_message, $data)
                    : null,
                data: $data,
            );
        } catch (Throwable $exception) {
            $this->logRenderFailure(
                description: $exception->getMessage(),
                templateKey: $template->key,
                data: $data,
            );

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderString(string $template, array $data): string
    {
        $rendered = preg_replace_callback('/{{\s*([A-Za-z0-9_.-]+)\s*}}/', function (array $matches) use ($data): string {
            $key = $matches[1];

            if (! Arr::has($data, $key)) {
                throw new RuntimeException("Missing notification template variable [{$key}].");
            }

            $value = Arr::get($data, $key);

            if ($value instanceof Htmlable) {
                return $value->toHtml();
            }

            if (is_array($value) || is_object($value)) {
                throw new RuntimeException("Notification template variable [{$key}] is not scalar.");
            }

            return e((string) $value);
        }, $template) ?? '';

        if (preg_match('/{{.*?}}/', $rendered, $matches) === 1) {
            throw new RuntimeException("Unresolved notification template variable [{$matches[0]}].");
        }

        return $rendered;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function logRenderFailure(string $description, ?string $templateKey, array $data): void
    {
        activity(BaseModel::ACTIVITY_LOG_SYSTEM)
            ->event(self::EVENT_TEMPLATE_RENDER_FAILED)
            ->withProperties([
                'template_key' => $templateKey,
                'data_keys' => array_keys(Arr::dot($data)),
            ])
            ->log(Str::limit($description, 1000, '...'));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function logEmailFailure(string $description, ?string $templateKey, array $properties = []): void
    {
        activity(BaseModel::ACTIVITY_LOG_SYSTEM)
            ->event(self::EVENT_EMAIL_FAILED)
            ->withProperties([
                ...$properties,
                'template_key' => $templateKey,
            ])
            ->log(Str::limit($description, 1000, '...'));
    }
}
