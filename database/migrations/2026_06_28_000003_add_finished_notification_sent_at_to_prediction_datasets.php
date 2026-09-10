<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // On a fresh install, the `predictions` schema hasn't been created
        // yet at this point — it's migrated separately, after this whole
        // batch finishes (see RunPredictionsMigrationsAfterDefaultMigrate).
        // Its init migration already defines this column directly, so
        // there's nothing to do here. This migration only matters for
        // environments where `datasets` already existed without the column.
        if (! Schema::connection('predictions')->hasTable('datasets')) {
            return;
        }

        if (Schema::connection('predictions')->hasColumn('datasets', 'finished_notification_sent_at')) {
            return;
        }

        Schema::connection('predictions')->table('datasets', function (Blueprint $table) {
            $table->timestamp('finished_notification_sent_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        if (! Schema::connection('predictions')->hasTable('datasets')) {
            return;
        }

        if (! Schema::connection('predictions')->hasColumn('datasets', 'finished_notification_sent_at')) {
            return;
        }

        Schema::connection('predictions')->table('datasets', function (Blueprint $table) {
            $table->dropColumn('finished_notification_sent_at');
        });
    }
};
