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
                'key' => NotificationTemplate::KEY_UPLOAD_RECEIVED,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_UPLOAD_RECEIVED],
                'notification_title' => 'Upload received',
                'notification_body' => 'Your dataset {{ dataset_name }} was received and is ready for configuration.',
                'email_subject' => 'MolMeDB upload received',
                'email_message' => '<p>Your dataset <strong>{{ dataset_name }}</strong> was received.</p><p>You can configure and track upload #{{ record_id }} using the private link below.</p><p><a href="{{ manage_url }}">Manage upload</a></p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_UPLOAD_STATUS_UPDATE,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_UPLOAD_STATUS_UPDATE],
                'notification_title' => 'Upload status updated',
                'notification_body' => 'Upload #{{ record_id }} is now in state: {{ state_label }}.',
                'email_subject' => 'MolMeDB upload status update',
                'email_message' => '<p>Your dataset <strong>{{ dataset_name }}</strong> is now in state: <strong>{{ state_label }}</strong>.</p><p><a href="{{ manage_url }}">Manage upload</a></p>{{ logs }}',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_UPLOAD_ADMIN_NEW_SUBMISSION,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_UPLOAD_ADMIN_NEW_SUBMISSION],
                'notification_title' => 'New lab upload',
                'notification_body' => 'Upload #{{ record_id }} was submitted by {{ uploader_label }}.',
                'email_subject' => 'MolMeDB: new lab upload',
                'email_message' => '<p>A new dataset <strong>{{ dataset_name }}</strong> was submitted by {{ uploader_label }}.</p><p><a href="{{ admin_url }}">Open upload #{{ record_id }}</a></p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_UPLOAD_ADMIN_REVIEW_REQUIRED,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_UPLOAD_ADMIN_REVIEW_REQUIRED],
                'notification_title' => 'Lab upload requires review',
                'notification_body' => 'Upload #{{ record_id }} requires administrator review.',
                'email_subject' => 'MolMeDB: lab upload requires review',
                'email_message' => '<p>Dataset <strong>{{ dataset_name }}</strong> from {{ uploader_label }} requires administrator review.</p><p><a href="{{ admin_url }}">Review upload #{{ record_id }}</a></p>',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => NotificationTemplate::KEY_UPLOAD_ADMIN_PROCESSING_ERROR,
                'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_UPLOAD_ADMIN_PROCESSING_ERROR],
                'notification_title' => 'Lab upload processing failed',
                'notification_body' => 'Upload #{{ record_id }} failed during processing.',
                'email_subject' => 'MolMeDB: lab upload processing failed',
                'email_message' => '<p>Dataset <strong>{{ dataset_name }}</strong> from {{ uploader_label }} failed during processing.</p><p><strong>Error:</strong> {{ error_message }}</p><p><a href="{{ admin_url }}">Open upload #{{ record_id }}</a></p>',
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
            ->whereIn('key', [
                NotificationTemplate::KEY_UPLOAD_RECEIVED,
                NotificationTemplate::KEY_UPLOAD_STATUS_UPDATE,
                NotificationTemplate::KEY_UPLOAD_ADMIN_NEW_SUBMISSION,
                NotificationTemplate::KEY_UPLOAD_ADMIN_REVIEW_REQUIRED,
                NotificationTemplate::KEY_UPLOAD_ADMIN_PROCESSING_ERROR,
            ])
            ->delete();
    }
};
