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
        Schema::create('membranes', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->nullable();
            $table->string('name', 150);
            $table->string('abbreviation', 30);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 80);
        });

        Schema::create('publications', function(Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->nullable();
            $table->string('citation', 1024)->nullable();
            $table->string('doi', 128)->nullable();
            $table->string('pmid', 50)->nullable();
            $table->string('title', 512)->nullable();
            $table->string('journal', 256)->nullable();
            $table->string('volume',50)->nullable();
            $table->string('issue',50)->nullable();
            $table->string('page',50)->nullable();
            $table->integer('year')->nullable();
            $table->date('publicated_date')->nullable();
            $table->datetimes();
            $table->softDeletes();
        });

        Schema::create('publication_has_authors', function (Blueprint $table) {
            $table->integer('publication_id');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('cascade');
            $table->integer('author_id');
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('restrict');
        });

        Schema::create('model_has_publications', function (Blueprint $table) {
            $table->integer('publication_id');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('restrict');
            $table->integer('model_id');
            $table->string('model_type', 256);
            $table->index(['model_id', 'model_type'], 'model_has_publications_model_id_index');
        });

        Schema::create('keywords', function (Blueprint $table)  {
            $table->id();
            $table->integer('model_id');
            $table->string('model_type', 256);
            $table->index(['model_id', 'model_type'], 'model_has_keywords_model_id_index');
            $table->string('value', 80);
            $table->index('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keywords');
        Schema::dropIfExists('model_has_publications');
        Schema::dropIfExists('publication_has_authors');
        Schema::dropIfExists('publications');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('membranes');
    }
};
