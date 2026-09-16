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
            $table->unique(
                ['structure_id', 'membrane_id', 'method_type', 'temperature'],
                'predictions_configuration_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('predictions', function (Blueprint $table): void {
            $table->dropUnique('predictions_configuration_unique');
        });
    }
};
