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
        Schema::table('upload_queue', function (Blueprint $table) {
            $table->string('guest_email')->nullable()->after('user_id');
            $table->string('guest_token')->nullable()->unique()->after('guest_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_queue', function (Blueprint $table) {
            $table->dropColumn(['guest_email', 'guest_token']);
        });
    }
};
