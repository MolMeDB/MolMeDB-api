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
        Schema::create('interaction_passives', function (Blueprint $table) {
            $table->id();
            $table->integer('dataset_id');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->boolean('is_visible')->nullable();
            $table->integer('membrane_id');
            $table->foreign('membrane_id')->references('id')->on('membranes')->onDelete('restrict');
            $table->integer('method_id');
            $table->foreign('method_id')->references('id')->on('methods')->onDelete('restrict');
            $table->integer('substance_id');
            $table->foreign('substance_id')->references('id')->on('substances')->onDelete('restrict');
            $table->double('temperature')->nullable();
            $table->string('charge', 40)->nullable();
            $table->integer('structure_ion_id')->nullable();
            $table->foreign('structure_ion_id')->references('id')->on('structure_ions')->onDelete('restrict');
            $table->integer('publication_id')->nullable();
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('restrict');
            $table->string('comment')->nullable();
            $table->float('xmin')->nullable();
            $table->float('xmin_accuracy')->nullable();
            $table->float('gpen')->nullable();
            $table->float('gpen_accuracy')->nullable();
            $table->float('gwat')->nullable();
            $table->float('gwat_accuracy')->nullable();
            $table->float('logk')->nullable();
            $table->float('logk_accuracy')->nullable();
            $table->float('logperm')->nullable();
            $table->float('logperm_accuracy')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interaction_passives');
    }
};
