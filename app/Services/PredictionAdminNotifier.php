<?php

namespace App\Services;

use App\Enums\PermissionEnums;
use App\Models\Config;
use App\Models\User;

class PredictionAdminNotifier
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Send a notification to every user holding the prediction-admin
     * permission (each respecting their own channel preference). Falls back
     * to the Config email when no such user exists yet, so a fresh install
     * without any admin assigned that permission still gets alerted.
     *
     * @param  array<string, mixed>  $data
     */
    public function notify(string $templateKey, array $data): void
    {
        $admins = User::query()
            ->permission(PermissionEnums::PREDICTION_DATASET_MANAGE_ALL->value)
            ->get();

        if ($admins->isEmpty()) {
            $email = trim((string) Config::get(Config::KEY_PREDICTION_ADMIN_EMAIL_FALLBACK, ''));

            if (filled($email)) {
                $this->notifications->sendEmailOnly($email, $templateKey, $data);
            }

            return;
        }

        foreach ($admins as $admin) {
            $this->notifications->send($admin, $templateKey, $data);
        }
    }
}
