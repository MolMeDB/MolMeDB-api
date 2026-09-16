<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'predictions';

    public function up(): void
    {
        Schema::connection($this->connection)->create('prediction_methods', function (Blueprint $table): void {
            // Immutable identifier - this is what predictions/datasets.method_type stores.
            $table->string('key', 20)->primary();
            $table->string('label');
            // Must match the method identifier configured on the remote prediction server.
            $table->string('remote_key')->unique();
            // Short code used in COSMO result file paths (e.g. "perm"), nullable - falls
            // back to a slug of the key when not set.
            $table->string('short_key', 10)->nullable();
            // Disables the method for new dataset/prediction uploads only; existing
            // predictions using a disabled method are still processed to completion.
            $table->boolean('enabled')->default(true);
            // Publications live in the default DB connection (a separate physical
            // database) so these are plain ids with no DB-level FK, same convention
            // already used e.g. by prediction_datasets.user_id. Required/nullable is
            // enforced on the PredictionMethodResource form instead (primary only).
            $table->unsignedBigInteger('primary_publication_id')->nullable();
            $table->unsignedBigInteger('secondary_publication_id')->nullable();
            $table->timestamps();
        });

        // Carry over the only method that was previously hardcoded as
        // Prediction::METHOD_COSMOPERM / config('prediction-workers.remote.methods.cosmoperm').
        // primary_publication_id is left null here - fill it in via the admin resource.
        DB::connection($this->connection)->table('prediction_methods')->insert([
            'key' => 'cosmoperm',
            'label' => 'CosmoPerm',
            'remote_key' => 'cosmoperm',
            'short_key' => 'perm',
            'enabled' => true,
            'primary_publication_id' => null,
            'secondary_publication_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // All existing predictions/datasets are 'cosmoperm', so this is safe to enforce now.
        Schema::connection($this->connection)->table('predictions', function (Blueprint $table): void {
            $table->foreign('method_type')
                ->references('key')->on('prediction_methods')
                ->onDelete('restrict');
        });

        Schema::connection($this->connection)->table('datasets', function (Blueprint $table): void {
            $table->foreign('method_type')
                ->references('key')->on('prediction_methods')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('datasets', function (Blueprint $table): void {
            $table->dropForeign(['method_type']);
        });

        Schema::connection($this->connection)->table('predictions', function (Blueprint $table): void {
            $table->dropForeign(['method_type']);
        });

        Schema::connection($this->connection)->dropIfExists('prediction_methods');
    }
};
