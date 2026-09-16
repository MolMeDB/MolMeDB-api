<?php

use App\Models\Config;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('configs')->insertOrIgnore([
            'key' => Config::KEY_LAB_UPLOAD_ADMIN_EMAIL_FALLBACK,
            'value' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('configs')
            ->where('key', Config::KEY_LAB_UPLOAD_ADMIN_EMAIL_FALLBACK)
            ->delete();
    }
};
