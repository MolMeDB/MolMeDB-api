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
        Schema::create('interactions_passive', function (Blueprint $table) {
            $table->id();
            $table->integer('dataset_id');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->integer('structure_id');
            $table->foreign('structure_id')->references('id')->on('structures')->onDelete('restrict');
            $table->integer('publication_id')->nullable();
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('restrict');
            $table->double('temperature')->nullable();
            $table->double('ph')->nullable();
            $table->string('charge', 40)->nullable();
            $table->string('note', 255)->nullable();
            $table->float('x_min')->nullable();
            $table->float('x_min_accuracy')->nullable();
            $table->float('gpen')->nullable();
            $table->float('gpen_accuracy')->nullable();
            $table->float('gwat')->nullable();
            $table->float('gwat_accuracy')->nullable();
            $table->float('logk')->nullable();
            $table->float('logk_accuracy')->nullable();
            $table->float('logperm')->nullable();
            $table->float('logperm_accuracy')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions_passive');
    }
};
