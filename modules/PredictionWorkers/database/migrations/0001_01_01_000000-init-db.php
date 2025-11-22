<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PredictionWorkers\Models\Prediction;

return new class extends Migration
{
    protected $connection = 'predictions';

    /**
     * Run the migrations.
     */
   public function up(): void
    {
        Schema::connection($this->connection)->create('structures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('remote_id')->nullable()->unique();
            $table->string('canonical_smiles', 4000)->nullable();
            $table->timestamps();
            $table->softDeletesDatetime();
        });

        Schema::connection($this->connection)->create('files', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->nullable();
            $table->string('name', 100)->nullable();
            $table->string('mime', 30)->nullable();
            $table->string('storage', 30)->nullable();
            $table->string('path')->nullable();
            $table->string('hash', 32)->index()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection($this->connection)->create('membranes', function (Blueprint $table) {
            $table->id();
            $table->integer('remote_id')->unique();
            $table->bigInteger('file_id')->unsigned()->nullable();
            $table->foreign('file_id')->references('id')->on('files')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('abbreviation', 30);
            $table->timestamps();
            $table->softDeletes();
        });

         Schema::connection($this->connection)->create('results', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('file_id')->unsigned()->nullable();
            $table->foreign('file_id')->references('id')->on('files')->restrictOnDelete();
            $table->json('data')->nullable();
            $table->timestamps(); 
        });

        Schema::connection($this->connection)->create('predictions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('result_id')->unsigned()->nullable();
            $table->foreign('result_id')->references('id')->on('results')->nullOnDelete();
            $table->bigInteger('structure_id')->unsigned();
            $table->foreign('structure_id')->references('id')->on('structures')->restrictOnDelete();
            $table->tinyInteger('state');
            $table->tinyInteger('step');
            $table->float('temperature', 1);
            $table->bigInteger('membrane_id')->unsigned();
            $table->foreign('membrane_id')->references('id')->on('membranes')->restrictOnDelete();
            $table->string('method_type', 20)->index();
            $table->tinyInteger('priority')->default(Prediction::PRIORITY_LOW);
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('datasets', function (Blueprint $table) {
            $table->id();
            $table->text('comment')->nullable();
            $table->string('token', 255)->unique()->nullable();
            $table->bigInteger('user_id')->unsigned()->nullable();
            // $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->float('temperature', 1);
            $table->bigInteger('membrane_id')->unsigned();
            $table->foreign('membrane_id')->references('id')->on('membranes')->restrictOnDelete();
            $table->string('method_type', 20)->index();
            $table->tinyInteger('priority')->default(Prediction::PRIORITY_LOW);
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('prediction_has_datasets', function (Blueprint $table) {
             $table->id();
             $table->bigInteger('prediction_id')->unsigned();
             $table->foreign('prediction_id')->references('id')->on('predictions')->cascadeOnDelete();
             $table->bigInteger('dataset_id')->unsigned();
             $table->foreign('dataset_id')->references('id')->on('datasets')->cascadeOnDelete();
             $table->timestamps();
        });

        Schema::connection($this->connection)->create('job_progress', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('model_id')->unsigned()->nullable();
            $table->string('model_type', 128)->nullable();
            $table->index(['model_id', 'model_type']);
            $table->string('queue', 50)->nullable();
            $table->string('context', 255)->index()->nullable();
            $table->json('payload')->nullable();
            $table->text('exception')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('job_progress');
        Schema::connection($this->connection)->dropIfExists('prediction_has_datasets');
        Schema::connection($this->connection)->dropIfExists('datasets');
        Schema::connection($this->connection)->dropIfExists('predictions');
        Schema::connection($this->connection)->dropIfExists('results');
        Schema::connection($this->connection)->dropIfExists('membranes');
        Schema::connection($this->connection)->dropIfExists('files');
        Schema::connection($this->connection)->dropIfExists('structures');
    }
};
