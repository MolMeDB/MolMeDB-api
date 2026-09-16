<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('notification_templates')->insertOrIgnore([
            'key' => NotificationTemplate::KEY_ACCOUNT_ROLE_CHANGED,
            'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_ACCOUNT_ROLE_CHANGED],
            'notification_title' => 'Your role was changed',
            'notification_body' => 'Your account roles are now: {{ roles }}.',
            'email_subject' => 'MolMeDB: your account role was changed',
            'email_message' => '<p>Your account roles were updated by an administrator.</p><p><strong>Current roles:</strong> {{ roles }}</p>',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->where('key', NotificationTemplate::KEY_ACCOUNT_ROLE_CHANGED)
            ->delete();
    }
};
