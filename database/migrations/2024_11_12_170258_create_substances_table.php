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
        Schema::create('substances', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 20);
            $table->string('name', 1024)->nullable();
            $table->string('fingerprint', 512)->nullable();
            $table->float('molecular_weight')->nullable();
            $table->float('logp')->nullable();
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('users')->on('id')->onDelete('restrict');
            // Identifiers moved to standalone table substance_identifiers
            $table->timestamps();
        });

        Schema::create('substance_identifiers', function (Blueprint $table) {
            $table->id();
            $table->integer('substance_id');
            $table->foreign('substance_id')->references('substances')->on('id')->onDelete('cascade');
            $table->integer('parent_id')->nullable();
            $table->foreign('parent_id')->references('substance_identifiers')->onDelete('restrict');
            $table->tinyInteger('server')->nullable();
            $table->tinyInteger('identifier');
            $table->string('value');
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('users')->on('id')->onDelete('set null');
            $table->tinyInteger('state');
            $table->string('state_message')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('is_active_message')->nullable();
            $table->tinyInteger('flag')->nullable();
            $table->timestamps();
        });

        Schema::create('substance_identifier_datasets', function (Blueprint $table) {
            $table->id();
            $table->integer('substance_identifier_id');
            $table->foreign('substance_identifier_id')->references('substance_identifiers')->on('id')->onDelete('restrict');
            $table->integer('id_dataset'); // Passive/Active interactions dataset
            $table->string('model');
            $table->timestamps();
        });

        Schema::create('substance_identifier_changes', function (Blueprint $table) {
            $table->id();
            $table->integer('old_id')->nullable();
            $table->foreign('old_id')->references('substance_identifiers')->on('id')->onDelete('cascade');
            $table->integer('new_id')->nullable();
            $table->foreign('new_id')->references('substance_identifiers')->on('id')->onDelete('cascade');
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('users')->on('id')->onDelete('set null');
            $table->string('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('substance_identifier_changes');
        Schema::dropIfExists('substance_identifier_datasets');
        Schema::dropIfExists('substance_identifiers');
        Schema::dropIfExists('substances');
    }
};
