<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'predictions';

    public function up(): void
    {
        Schema::connection($this->connection)->table('predictions', function (Blueprint $table) {
            $table->string('remote_method', 50)->nullable()->index()->after('method_type');
            $table->string('remote_calculation_id', 80)->nullable()->unique()->after('remote_method');
            $table->string('remote_molecule_id', 80)->nullable()->index()->after('remote_calculation_id');
            $table->string('remote_status', 50)->nullable()->index()->after('remote_molecule_id');
            $table->string('remote_current_step', 50)->nullable()->index()->after('remote_status');
            $table->timestamp('remote_submitted_at')->nullable()->after('remote_current_step');
            $table->timestamp('remote_heartbeat_at')->nullable()->index()->after('remote_submitted_at');
            $table->timestamp('remote_last_status_at')->nullable()->index()->after('remote_heartbeat_at');
            $table->timestamp('remote_finished_at')->nullable()->after('remote_last_status_at');
            $table->text('remote_error_message')->nullable()->after('remote_finished_at');
            $table->json('logs')->nullable()->after('remote_error_message');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('predictions', function (Blueprint $table) {
            $table->dropColumn([
                'remote_method',
                'remote_calculation_id',
                'remote_molecule_id',
                'remote_status',
                'remote_current_step',
                'remote_submitted_at',
                'remote_heartbeat_at',
                'remote_last_status_at',
                'remote_finished_at',
                'remote_error_message',
                'logs',
            ]);
        });
    }
};
