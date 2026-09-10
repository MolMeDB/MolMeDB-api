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
            'key' => NotificationTemplate::KEY_FEEDBACK_ADMIN_NEW_SUBMISSION,
            'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_FEEDBACK_ADMIN_NEW_SUBMISSION],
            'notification_title' => 'New feedback',
            'notification_body' => 'New feedback from {{ email }} ({{ context }}).',
            'email_subject' => 'MolMeDB: New feedback!',
            'email_message' => '<p><strong>From:</strong> {{ email }}</p><p><strong>Context:</strong> {{ context }}</p><p><strong>Message:</strong></p><p>{{ message }}</p><p>You can respond to the feedback by replying to this email.</p>',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->where('key', NotificationTemplate::KEY_FEEDBACK_ADMIN_NEW_SUBMISSION)
            ->delete();
    }
};
