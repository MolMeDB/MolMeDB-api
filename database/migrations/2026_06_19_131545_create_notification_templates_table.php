<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('notification_title');
            $table->text('notification_body');
            $table->string('email_subject')->nullable();
            $table->text('email_message')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        DB::table('notification_templates')->insert([
            'key' => NotificationTemplate::KEY_FEEDBACK_ACCEPTED,
            'name' => NotificationTemplate::keyOptions()[NotificationTemplate::KEY_FEEDBACK_ACCEPTED],
            'notification_title' => 'Feedback received',
            'notification_body' => 'Thank you for your feedback. We received your message from {{ context }}.',
            'email_subject' => 'MolMeDB feedback received',
            'email_message' => '<p>Thank you for your feedback.</p><p>We received your message from <strong>{{ context }}</strong>.</p>',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('notification_templates')->where('key', NotificationTemplate::KEY_FEEDBACK_ACCEPTED)->delete();
        Schema::dropIfExists('notification_templates');
    }
};
