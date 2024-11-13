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
        Schema::create('proteins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('uniprot_id', 50);
            $table->string('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps(); 
        });
        
        Schema::create('interaction_actives', function (Blueprint $table) {
            $table->id();
            $table->integer('dataset_id');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->boolean('is_visible')->nullable();
            $table->integer('substance_id');
            $table->foreign('substance_id')->references('id')->on('substances')->onDelete('restrict');
            $table->integer('structure_ion_id')->nullable();
            $table->foreign('structure_ion_id')->references('id')->on('structure_ions')->onDelete('restrict');
            $table->integer('protein_id');
            $table->foreign('protein_id')->references('id')->on('proteins')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interaction_actives');
        Schema::dropIfExists('proteins');
    }
};
