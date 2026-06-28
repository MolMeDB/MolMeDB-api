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
                'key' => NotificationTemplate::KEY_PREDICTION_ADMIN_NEW_SUBMISSION,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_PREDICTION_ADMIN_NEW_SUBMISSION],
                'notification_title' => 'New prediction submission',
                'notification_body' => 'New prediction job "{{ comment }}" submitted by {{ uploader_label }} ({{ total }} molecules, {{ membrane }}, {{ method }}).',
                'email_subject' => 'MolMeDB: new prediction submission',
                'email_message' => '<p>A new prediction job was submitted.</p><table style="border-collapse:collapse"><tr><td style="padding:4px 12px 4px 0"><strong>Submitted by</strong></td><td>{{ uploader_label }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Comment</strong></td><td>{{ comment }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Molecules</strong></td><td>{{ total }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Membrane</strong></td><td>{{ membrane }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Method</strong></td><td>{{ method }}</td></tr></table><p><a href="{{ dataset_url }}">View dataset</a></p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_PREDICTION_ADMIN_STATS_REPORT,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_PREDICTION_ADMIN_STATS_REPORT],
                'notification_title' => 'Prediction statistics: {{ period_label }}',
                'notification_body' => '{{ period_label }} stats: {{ added }} added, {{ optimized }} optimized, {{ cosmo_done }} COSMO done, {{ failed }} failed.',
                'email_subject' => 'MolMeDB: prediction statistics ({{ period_label }})',
                'email_message' => '<p><strong>Prediction statistics — {{ period_label }}</strong><br><em>{{ period_from }} – {{ period_to }}</em></p><table style="border-collapse:collapse"><tr><td style="padding:4px 12px 4px 0"><strong>New predictions added</strong></td><td>{{ added }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Optimized (reached COSMO or later)</strong></td><td>{{ optimized }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>COSMO calculations completed</strong></td><td>{{ cosmo_done }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Newly failed</strong></td><td>{{ failed }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Currently running</strong></td><td>{{ running }}</td></tr><tr><td style="padding:4px 12px 4px 0"><strong>Total predictions in DB</strong></td><td>{{ total_all }}</td></tr></table>',
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
                NotificationTemplate::KEY_PREDICTION_ADMIN_NEW_SUBMISSION,
                NotificationTemplate::KEY_PREDICTION_ADMIN_STATS_REPORT,
            ])
            ->delete();
    }
};
