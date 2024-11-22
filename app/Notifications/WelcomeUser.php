<?php

namespace App\Notifications;

use App\Mail\UserWelcome;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeUser extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public User $user)
    {
        // $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(env('MAIL_FROM_ADDRESS', env('MAIL_FROM_NAME')))
            ->subject('Welcome new MolMeDB user!')
            ->view(
                ['mail.welcome', 'mail.welcome-text'], 
                [
                    'user' => $this->user,
                    'app_name' => env('APP_NAME'),
                    'validation_link' => 'http://test.molmedb.org', // TODO
                    'app_url' => env('APP_URL'),
                ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
