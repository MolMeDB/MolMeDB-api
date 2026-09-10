<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class TemplatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $notificationTitle,
        public readonly string $notificationBody,
        public readonly ?string $emailSubject = null,
        public readonly ?string $emailMessage = null,
        public readonly array $data = [],
        public readonly ?string $preferencesUrl = null,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->hasEmail() ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->emailSubject)
            ->greeting(' ')
            ->line(new HtmlString($this->emailMessage));

        if ($this->preferencesUrl) {
            $mail->line(new HtmlString(
                '<hr style="border:none;border-top:1px solid #e0e0e0;margin:24px 0 12px;">'
                .'<p style="font-size:12px;color:#888888;">'
                .'Don\'t want to receive this notification? '
                .'<a href="'.e($this->preferencesUrl).'">Manage your notification preferences</a>.'
                .'</p>'
            ));
        }

        return $mail->salutation(' ');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toUserNotificationData();
    }

    /**
     * @return array{title: string, body: string, email_subject: ?string, email_message: ?string, data: array<string, mixed>}
     */
    public function toUserNotificationData(): array
    {
        return [
            'title' => $this->notificationTitle,
            'body' => $this->notificationBody,
            'email_subject' => $this->emailSubject,
            'email_message' => $this->emailMessage,
            'data' => $this->data,
        ];
    }

    private function hasEmail(): bool
    {
        return filled($this->emailSubject) && filled($this->emailMessage);
    }
}
