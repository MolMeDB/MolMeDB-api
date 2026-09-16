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
            [
                'key' => NotificationTemplate::KEY_SYSTEM_ADMIN_BACKUP_FAILED,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_SYSTEM_ADMIN_BACKUP_FAILED],
                'notification_title' => 'Database backup failed',
                'notification_body' => 'The "{{ label }}" backup job failed: {{ error }}',
                'email_subject' => 'MolMeDB: database backup failed',
                'email_message' => '<p>The <strong>{{ label }}</strong> backup job failed.</p><p><strong>Error:</strong> {{ error }}</p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_SYSTEM_ADMIN_JOB_FAILED,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_SYSTEM_ADMIN_JOB_FAILED],
                'notification_title' => 'Background job failure',
                'notification_body' => '{{ count }} background job(s)/scheduled task(s) failed recently.{{ items }}',
                'email_subject' => 'MolMeDB: background job failure',
                'email_message' => '<p>{{ count }} background job(s)/scheduled task(s) failed recently.</p>{{ items }}',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_SYSTEM_ADMIN_NOTIFICATION_FAILURE,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_SYSTEM_ADMIN_NOTIFICATION_FAILURE],
                'notification_title' => 'Notification delivery failure',
                'notification_body' => 'A notification failed to render or send (template: {{ template_key }}): {{ error }}',
                'email_subject' => 'MolMeDB: notification delivery failure',
                'email_message' => '<p>A notification failed to render or send.</p><p><strong>Template:</strong> {{ template_key }}<br><strong>Error:</strong> {{ error }}</p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_PREDICTION_ADMIN_REMOTE_SERVICE_DOWN,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_PREDICTION_ADMIN_REMOTE_SERVICE_DOWN],
                'notification_title' => 'Remote prediction service unavailable',
                'notification_body' => 'The remote prediction service is unavailable: {{ error }}',
                'email_subject' => 'MolMeDB: remote prediction service unavailable',
                'email_message' => '<p>The remote prediction (Metacentrum) service is unavailable or disabled.</p><p><strong>Error:</strong> {{ error }}</p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->whereIn('key', [
                NotificationTemplate::KEY_SYSTEM_ADMIN_BACKUP_FAILED,
                NotificationTemplate::KEY_SYSTEM_ADMIN_JOB_FAILED,
                NotificationTemplate::KEY_SYSTEM_ADMIN_NOTIFICATION_FAILURE,
                NotificationTemplate::KEY_PREDICTION_ADMIN_REMOTE_SERVICE_DOWN,
            ])
            ->delete();
    }
};
