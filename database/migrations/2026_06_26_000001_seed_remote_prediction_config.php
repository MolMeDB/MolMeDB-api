<?php

use App\Models\Config;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('configs')->insertOrIgnore([
            [
                'key' => Config::KEY_REMOTE_PREDICTION_ENABLED,
                'value' => config('prediction-workers.remote.enabled') ? '1' : '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => Config::KEY_REMOTE_PREDICTION_URL,
                'value' => config('prediction-workers.remote.base_url'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('configs')
            ->whereIn('key', [
                Config::KEY_REMOTE_PREDICTION_ENABLED,
                Config::KEY_REMOTE_PREDICTION_URL,
            ])
            ->delete();
    }
};
