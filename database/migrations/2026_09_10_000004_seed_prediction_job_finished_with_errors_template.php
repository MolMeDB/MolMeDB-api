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
            'key' => NotificationTemplate::KEY_PREDICTION_JOB_FINISHED_WITH_ERRORS,
            'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_PREDICTION_JOB_FINISHED_WITH_ERRORS],
            'notification_title' => 'Prediction job finished with errors',
            'notification_body' => 'Your prediction dataset "{{ comment }}" finished with some errors. {{ done }} completed, {{ failed }} failed out of {{ total }}.',
            'email_subject' => 'MolMeDB: prediction job finished with errors',
            'email_message' => '<p>Your prediction job <strong>{{ comment }}</strong> has finished, but some predictions failed.</p><p><strong>Completed:</strong> {{ done }} / {{ total }}<br><strong>Failed:</strong> {{ failed }}<br><strong>Membrane:</strong> {{ membrane }}<br><strong>Method:</strong> {{ method }}</p><p><a href="{{ dataset_url }}">View results</a></p>',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->where('key', NotificationTemplate::KEY_PREDICTION_JOB_FINISHED_WITH_ERRORS)
            ->delete();
    }
};
