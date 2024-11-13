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
            // TODO: Add cosmo_file_link
            $table->tinyInteger('type')->nullable();
            $table->string('name', 150);
            $table->string('description')->nullable();
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('publications', function(Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->nullable();
            $table->string('citation')->nullable();
            $table->string('doi', 512)->nullable();
            $table->string('pmid', 50)->nullable();
            $table->string('title', 512)->nullable();
            $table->string('authors')->nullable();
            $table->string('journal', 256)->nullable();
            $table->string('volume',50)->nullable();
            $table->string('issue',50)->nullable();
            $table->string('page',50)->nullable();
            $table->integer('year')->nullable();
            $table->date('publicated_date')->nullable();
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->integer('total_passive_interactions')->nullable();
            $table->integer('total_active_interactions')->nullable();
            $table->integer('total_substances')->nullable();
            $table->datetimes();
        });

        Schema::create('membrane_publication', function (Blueprint $table) {
            $table->integer('membrane_id');
            $table->integer('publication_id');

            $table->foreign('membrane_id')->references('id')->on('membranes')->onDelete('cascade');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('restrict');
        });

        Schema::create('keywords', function (Blueprint $table)  {
            $table->id();
            $table->integer('model_id');
            $table->string('model', 30);
            $table->string('value', 80);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keywords');
        Schema::dropIfExists('membrane_publication');
        Schema::dropIfExists('publications');
        Schema::dropIfExists('membranes');
    }
};
