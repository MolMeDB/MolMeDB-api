<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'predictions';

    public function up(): void
    {
        Schema::connection($this->connection)->create('prediction_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stats_date')->index();
            $table->string('server_url')->index();
            $table->json('payload');
            $table->timestamp('fetched_at')->index();
            $table->timestamps();

            $table->unique(['server_url', 'stats_date']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('prediction_stats');
    }
};
