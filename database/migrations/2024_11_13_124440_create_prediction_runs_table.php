<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prediction_runs', function (Blueprint $table) {
            $table->id();
            $table->integer('structure_id');
            $table->foreign('structure_id')->on('id')->references('structures')->onDelete('restrict');
            $table->tinyInteger('state')->nullable();
            $table->tinyInteger('status');
            $table->tinyInteger('force_run')->nullable();
            $table->double('temperature');
            $table->integer('membrane_id');
            $table->foreign('membrane_id')->on('id')->references('membranes')->onDelete('restrict');
            $table->tinyInteger('method');
            $table->tinyInteger('priority');
            $table->timestamp('next_remote_check')->useCurrent();
            $table->timestamps();
        });

        Schema::create('prediction_datasets', function(Blueprint $table) {
            $table->id();
            $table->tinyInteger('priority')->default(1); // TODO
            $table->string('comment', 512)->nullable();
            $table->string('token', 120)->nullable();
            $table->tinyInteger('notify_state')->nullable();
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->on('id')->references('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('prediction_dataset_prediction_run', function(Blueprint $table) {
            $table->integer('prediction_dataset_id');
            $table->foreign('prediction_dataset_id')->on('id')->references('prediction_datasets')->onDelete('cascade');
            $table->integer('prediction_run_id');
            $table->foreign('prediction_run_id')->on('id')->references('prediction_runs')->onDelete('restrict');
        });

        Schema::create('prediction_run_logs', function(Blueprint $table)
        {
            $table->id();
            $table->integer('prediction_run_id');
            $table->foreign('prediction_run_id')->on('id')->references('prediction_runs')->onDelete('cascade');
            $table->string('message');
            $table->timestamp('datetime')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_run_logs');
        Schema::dropIfExists('prediction_dataset_prediction_run');
        Schema::dropIfExists('prediction_datasets');
        Schema::dropIfExists('prediction_runs');
    }
};
