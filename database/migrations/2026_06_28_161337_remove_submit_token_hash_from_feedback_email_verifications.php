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
        Schema::table('feedback_email_verifications', function (Blueprint $table) {
            $table->dropColumn('submit_token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback_email_verifications', function (Blueprint $table) {
            $table->string('submit_token_hash')->nullable()->after('code_hash');
        });
    }
};
