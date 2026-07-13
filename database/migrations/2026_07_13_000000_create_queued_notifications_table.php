<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('queued_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('group_key');
            $table->string('event');
            $table->nullableMorphs('notifiable');
            $table->json('data')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['group_key', 'notified_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queued_notifications');
    }
};
