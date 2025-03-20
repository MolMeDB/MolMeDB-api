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
            $table->timestamps(); 
            $table->softDeletes();
        });
        
        Schema::create('interactions_active', function (Blueprint $table) {
            $table->id();
            $table->integer('dataset_id');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->integer('structure_id');
            $table->foreign('structure_id')->references('id')->on('structures')->onDelete('restrict');
            $table->integer('protein_id');
            $table->foreign('protein_id')->references('id')->on('proteins')->onDelete('restrict');
            $table->integer('publication_id');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('restrict');
            $table->tinyInteger('type');
            $table->double('temperature')->nullable();
            $table->double('ph')->nullable();
            $table->string('charge', 40)->nullable();
            $table->string('note', 255)->nullable();
            $table->double('km')->nullable();
            $table->double('km_accuracy')->nullable();
            $table->double('ec50')->nullable();
            $table->double('ec50_accuracy')->nullable();
            $table->double('ki')->nullable();
            $table->double('ki_accuracy')->nullable();
            $table->double('ic50')->nullable();
            $table->double('ic50_accuracy')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions_active');
        Schema::dropIfExists('proteins');
    }
};
