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
                'key' => NotificationTemplate::KEY_EXPORT_READY,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_EXPORT_READY],
                'notification_title' => 'Export ready',
                'notification_body' => 'Your data export is ready for download. It will be available for 2 days.',
                'email_subject' => 'MolMeDB: your export is ready',
                'email_message' => '<p>Your data export is ready for download.</p><p><a href="{{ manage_url }}">Download export</a></p><p>It will be available for 2 days.</p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_EXPORT_FAILED,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_EXPORT_FAILED],
                'notification_title' => 'Export failed',
                'notification_body' => 'Your data export could not be completed.',
                'email_subject' => 'MolMeDB: your export failed',
                'email_message' => '<p>Your data export could not be completed.</p><p><strong>Error:</strong> {{ error_message }}</p><p><a href="{{ manage_url }}">Try again</a></p>',
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
                NotificationTemplate::KEY_EXPORT_READY,
                NotificationTemplate::KEY_EXPORT_FAILED,
            ])
            ->delete();
    }
};
