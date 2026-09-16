<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('notification_templates')->insertOrIgnore([
            [
                'key' => NotificationTemplate::KEY_UPLOAD_ADMIN_DIGEST,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_UPLOAD_ADMIN_DIGEST],
                'notification_title' => 'Lab upload digest',
                'notification_body' => '{{ count }} lab upload update(s) since the last digest.',
                'email_subject' => 'MolMeDB: lab upload digest ({{ count }})',
                'email_message' => '<p>{{ count }} lab upload update(s) since the last digest:</p>{{ summary }}',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('notification_templates')
            ->where('key', NotificationTemplate::KEY_UPLOAD_ADMIN_DIGEST)
            ->delete();
    }
};
