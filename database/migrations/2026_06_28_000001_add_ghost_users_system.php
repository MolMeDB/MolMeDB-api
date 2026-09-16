<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ghost users: no password, linked via email verification
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_ghost')->default(false)->after('remember_token');
            $table->string('password')->nullable()->change();
        });

        // Link each email verification to the ghost (or real) user it created/found
        Schema::table('feedback_email_verifications', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('email')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feedback_email_verifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_ghost');
            $table->string('password')->nullable(false)->change();
        });
    }
};
