<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PredictionWorkers\Models\Prediction;

return new class extends Migration
{
    protected $connection = 'predictions';

    public function up(): void
    {
        Schema::connection($this->connection)->table('predictions', function (Blueprint $table): void {
            $table->tinyInteger('priority')->default(Prediction::PRIORITY_MEDIUM)->change();
        });

        Schema::connection($this->connection)->table('datasets', function (Blueprint $table): void {
            $table->tinyInteger('priority')->default(Prediction::PRIORITY_MEDIUM)->change();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('predictions', function (Blueprint $table): void {
            $table->tinyInteger('priority')->default(Prediction::PRIORITY_LOW)->change();
        });

        Schema::connection($this->connection)->table('datasets', function (Blueprint $table): void {
            $table->tinyInteger('priority')->default(Prediction::PRIORITY_LOW)->change();
        });
    }
};
