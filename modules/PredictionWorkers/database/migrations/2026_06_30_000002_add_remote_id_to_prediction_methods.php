<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'predictions';

    public function up(): void
    {
        Schema::connection($this->connection)->table('prediction_methods', function (Blueprint $table): void {
            // Links to App\Models\Method.id in the default connection (same
            // cross-database convention as membranes.remote_id -> Membrane.id).
            // Determines which Method new interaction/dataset rows get tagged
            // with when finished predictions are imported. Nullable at the DB
            // level only because the cosmoperm seed row predates a known
            // value - required is enforced on the resource form.
            $table->unsignedBigInteger('remote_id')->nullable()->unique()->after('key');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('prediction_methods', function (Blueprint $table): void {
            $table->dropColumn('remote_id');
        });
    }
};
