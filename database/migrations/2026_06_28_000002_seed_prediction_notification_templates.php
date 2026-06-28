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
                'key' => NotificationTemplate::KEY_PREDICTION_JOB_SUBMITTED,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_PREDICTION_JOB_SUBMITTED],
                'notification_title' => 'Prediction job submitted',
                'notification_body' => 'Your prediction dataset "{{ comment }}" ({{ total }} molecules) was submitted and is being processed.',
                'email_subject' => 'MolMeDB: prediction job submitted',
                'email_message' => '<p>Your prediction job <strong>{{ comment }}</strong> was submitted.</p><p><strong>Molecules:</strong> {{ total }}<br><strong>Membrane:</strong> {{ membrane }}<br><strong>Method:</strong> {{ method }}</p><p>You can track the progress using the link below.</p><p><a href="{{ dataset_url }}">View dataset progress</a></p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_PREDICTION_JOB_FINISHED,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_PREDICTION_JOB_FINISHED],
                'notification_title' => 'Prediction job finished',
                'notification_body' => 'Your prediction dataset "{{ comment }}" has finished. {{ done }} completed, {{ failed }} failed out of {{ total }}.',
                'email_subject' => 'MolMeDB: prediction job finished',
                'email_message' => '<p>Your prediction job <strong>{{ comment }}</strong> has finished.</p><p><strong>Completed:</strong> {{ done }} / {{ total }}<br><strong>Failed:</strong> {{ failed }}<br><strong>Membrane:</strong> {{ membrane }}<br><strong>Method:</strong> {{ method }}</p><p><a href="{{ dataset_url }}">View results</a></p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_PREDICTION_JOB_DAILY_PROGRESS,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_PREDICTION_JOB_DAILY_PROGRESS],
                'notification_title' => 'Prediction job progress update',
                'notification_body' => 'Daily update for "{{ comment }}": {{ done }} done, {{ running }} running, {{ failed }} failed out of {{ total }}.',
                'email_subject' => 'MolMeDB: daily prediction progress',
                'email_message' => '<p>Here is your daily progress update for prediction job <strong>{{ comment }}</strong>:</p><table style="border-collapse:collapse"><tr><td style="padding:4px 12px 4px 0"><strong>Total molecules</strong></td><td>{{ total }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Completed</strong></td><td>{{ done }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Running</strong></td><td>{{ running }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Pending</strong></td><td>{{ pending }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Failed</strong></td><td>{{ failed }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Membrane</strong></td><td>{{ membrane }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Method</strong></td><td>{{ method }}</td></tr></table><p><a href="{{ dataset_url }}">View dataset</a></p>',
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
                NotificationTemplate::KEY_PREDICTION_JOB_SUBMITTED,
                NotificationTemplate::KEY_PREDICTION_JOB_FINISHED,
                NotificationTemplate::KEY_PREDICTION_JOB_DAILY_PROGRESS,
            ])
            ->delete();
    }
};
