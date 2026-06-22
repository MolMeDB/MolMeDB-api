<?php

namespace App\Notifications;

use App\Models\FeedbackSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackReceived extends Notification
{
    use Queueable;

    public function __construct(private readonly FeedbackSubmission $feedback) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->feedback->context ?? '—';
        $email = $this->feedback->email;
        $isRegistered = $this->feedback->user?->id !== null;

        return (new MailMessage)
            ->replyTo($email, $this->feedback->user?->name ?? $email)
            ->subject('MolMeDB: New feedback!')
            ->greeting('New feedback recorded')
            ->line("**From:** {$email}".($isRegistered ? ' (registered)' : ' (guest)'))
            ->line("**Context:** {$url}")
            ->line('**Message:**')
            ->line($this->feedback->message)
            ->line('-------------------------------')
            ->line('**You can respond to the feedback by replying to this email.**')
            ->salutation('Regards, MolMeDB team.');
    }
}
