<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'predictions';

    public function up(): void
    {
        Schema::connection($this->connection)->table('predictions', function (Blueprint $table): void {
            $table->timestamp('remote_paused_at')->nullable()->index()->after('remote_finished_at');
            $table->text('remote_pause_reason')->nullable()->after('remote_paused_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('predictions', function (Blueprint $table): void {
            $table->dropColumn(['remote_paused_at', 'remote_pause_reason']);
        });
    }
};
