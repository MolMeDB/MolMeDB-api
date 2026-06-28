<?php

namespace App\Services;

use App\Models\Config;

class PredictionAdminNotifier
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Send a notification to all admin-role users + optional email fallback from Config.
     *
     * @param  array<string, mixed>  $data
     */
    public function notify(string $templateKey, array $data): void
    {
        $email = trim((string) Config::get(Config::KEY_PREDICTION_ADMIN_EMAIL_FALLBACK, ''));

        if (filled($email)) {
            $this->notifications->sendEmailOnly($email, $templateKey, $data);
        }
    }
}
