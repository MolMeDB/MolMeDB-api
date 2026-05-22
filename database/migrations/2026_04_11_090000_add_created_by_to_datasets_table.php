<?php

use App\Models\Dataset;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('datasets', function (Blueprint $table): void {
            $table->foreignId('created_by')
                ->nullable()
                ->after('dataset_group_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        if (! Schema::hasTable(config('activitylog.table_name'))) {
            return;
        }

        $creatorSubquery = DB::table(config('activitylog.table_name'))
            ->select('causer_id')
            ->whereColumn('subject_id', 'datasets.id')
            ->where('subject_type', Dataset::class)
            ->where('causer_type', User::class)
            ->orderBy('id')
            ->limit(1);

        DB::table('datasets')
            ->whereNull('created_by')
            ->update([
                'created_by' => $creatorSubquery,
            ]);
    }

    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
