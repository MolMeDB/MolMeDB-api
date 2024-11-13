<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

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
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            // Identifiers moved to standalone table substance_identifiers
            $table->timestamps();
        });

        Schema::create('substance_identifiers', function (Blueprint $table) {
            $table->id();
            $table->integer('substance_id');
            $table->foreign('substance_id')->references('id')->on('substances')->onDelete('cascade');
            $table->integer('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('substance_identifiers')->onDelete('restrict');
            $table->tinyInteger('server')->nullable();
            $table->tinyInteger('type');
            $table->string('value');
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->tinyInteger('state');
            $table->string('state_message')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('is_active_message')->nullable();
            $table->tinyInteger('flag')->nullable();
            $table->timestamps();
        });

        Schema::create('substance_identifier_validations', function (Blueprint $table) {
            $table->id();
            $table->integer('substance_identifier_id');
            $table->foreign('substance_identifier_id')->references('id')->on('substance_identifiers')->onDelete('restrict');
            $table->integer('user_id'); 
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->tinyInteger('state');
            $table->string('message', 1024)->nullable();
            $table->timestamps();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });

        // Schema::create('upload_queue', function (Blueprint $table) {
        //     $table->id();
        //     $table->tinyInteger('type');
        //     $table->tinyInteger('state');
        //     $table->integer('user_id')->nullable();
        //     $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        //     $table->text('settings')->nullable();
        //     $table->integer('file_id');
        //     $table->foreign('file_id')->references('id')->on('files')->onDelete('restrict');
        //     $table->string('run_info')->nullable();
        //     $table->timestamps(); 
        // });

        Schema::create('datasets', function(Blueprint $table) {
            $table->id();
            // Upload info
            $table->tinyInteger('upload_state');
            $table->text('upload_settings')->nullable();
            $table->integer('file_id');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('restrict');
            $table->string('upload_run_info')->nullable();
            // Dataset info
            // $table->integer('upload_queue_id')->nullable();
            // $table->foreign('upload_queue_id')->references('id')->on('upload_queue')->onDelete('restrict');
            $table->tinyInteger('type');
            $table->tinyInteger('special_type')->nullable();
            $table->boolean('is_visible')->default(false);
            $table->string('name', 256);
            $table->integer('membrane_id')->nullable();
            $table->foreign('membrane_id')->references('id')->on('membranes')->onDelete('restrict');
            $table->integer('method_id')->nullable();
            $table->foreign('method_id')->references('id')->on('methods')->onDelete('restrict');
            $table->integer('publication_id')->nullable();
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('restrict');
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });


        Schema::create('substance_identifier_dataset', function (Blueprint $table) {
            $table->id();
            $table->integer('substance_identifier_id');
            $table->foreign('substance_identifier_id')->references('id')->on('substance_identifiers')->onDelete('restrict');
            $table->integer('dataset_id');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('substance_identifier_changes', function (Blueprint $table) {
            $table->id();
            $table->integer('old_id')->nullable();
            $table->foreign('old_id')->references('id')->on('substance_identifiers')->onDelete('cascade');
            $table->integer('new_id')->nullable();
            $table->foreign('new_id')->references('id')->on('substance_identifiers')->onDelete('cascade');
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->string('message')->nullable();
            $table->timestamp('datetime')->default('CURRENT_TIMESTAMP');
        });

        Schema::create('structures', function(Blueprint $table) {
            $table->id();
            $table->integer('substance_id')->nullable();
            $table->foreign('substance_id')->references('id')->on('substances')->onDelete('set null'); 
            $table->string('smiles')->unique();
            $table->double('ioninzation_ph_start')->nullable();
            $table->double('ionization_ph_end')->nullable();
            $table->timestamp('ionization_datetime')->nullable();
            $table->timestamps();  
        });

        Schema::create('structure_ions', function (Blueprint $table) {
            $table->id();
            $table->integer('structure_id');
            $table->foreign('structure_id')->references('id')->on('structures')->onDelete('cascade');
            $table->string('smiles')->unique();
            $table->tinyInteger('optimization_flag')->nullable(); 
        });

        Schema::create('enum_types', function (Blueprint $table)
        {
            $table->id();
            $table->string('name', 255);
            $table->string('content')->nullable();
            $table->tinyInteger('type');
        });

        Schema::create('enum_type_links', function (Blueprint $table) { 
            $table->id();
            $table->integer('enum_type_id');
            $table->foreign('enum_type_id')->references('id')->on('enum_types')->onDelete('restrict');
            $table->integer('enum_type_parent_id');
            $table->foreign('enum_type_parent_id')->references('id')->on('enum_types')->onDelete('restrict');
            $table->integer('enum_type_link_id')->nullable();
            $table->foreign('enum_type_link_id')->references('id')->on('enum_type_links')->onDelete('restrict');
            $table->string('data')->nullable();
            $table->string('reg_exp', 255)->nullable();
        });

        Schema::create('fragments', function (Blueprint $table)
        {
            $table->id();
            $table->string('smiles')->unique(); 
        });

        Schema::create('fragment_options', function (Blueprint $table){
            $table->id();
            $table->integer('parent_id');
            $table->foreign('parent_id')->on('id')->references('fragments')->onDelete('cascade');
            $table->integer('child_id');
            $table->foreign('child_id')->on('id')->references('fragments')->onDelete('cascade');
            $table->string('deletions', 20)->nullable();
        });

        Schema::create('fragment_enum_type', function (Blueprint $table) {
            $table->integer('fragment_id');
            $table->foreign('fragment_id')->on('id')->references('fragments')->onDelete('cascade');
            $table->integer('enum_type_id');
            $table->foreign('enum_type_id')->on('id')->references('enum_types')->onDelete('restrict');
        });

        Schema::create('fragment_structure', function (Blueprint $table) {
            $table->integer('fragment_id');
            $table->foreign('fragment_id')->on('id')->references('fragments')->onDelete('restrict');
            $table->integer('structure_id');
            $table->foreign('structure_id')->on('id')->references('structures')->onDelete('cascade');
        });

        
        Schema::create('enum_type_link_membrane', function(Blueprint $table)
        {
            $table->integer('enum_type_link_id');
            $table->foreign('enum_type_link_id')->on('id')->references('enum_type_links')->onDelete('restrict');
            $table->integer('membrane_id');
            $table->foreign('membrane_id')->on('id')->references('membranes')->onDelete('cascade');
        });
        
        Schema::create('enum_type_link_method', function(Blueprint $table)
        {
            $table->integer('enum_type_link_id');
            $table->foreign('enum_type_link_id')->on('id')->references('enum_type_links')->onDelete('restrict');
            $table->integer('method_id');
            $table->foreign('method_id')->on('id')->references('methods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enum_type_link_method');
        Schema::dropIfExists('enum_type_link_membrane');
        Schema::dropIfExists('fragment_structure');
        Schema::dropIfExists('fragment_enum_type');
        Schema::dropIfExists('fragment_options');
        Schema::dropIfExists('fragments');
        Schema::dropIfExists('enum_type_links');
        Schema::dropIfExists('enum_types');
        Schema::dropIfExists('structure_ions');
        Schema::dropIfExists('structures');
        Schema::dropIfExists('substance_identifier_changes');
        Schema::dropIfExists('substance_identifier_dataset');
        Schema::dropIfExists('datasets');
        // Schema::dropIfExists('upload_queue');
        Schema::dropIfExists('files');
        Schema::dropIfExists('substance_identifier_validations');
        Schema::dropIfExists('substance_identifiers');
        Schema::dropIfExists('substances');
    }
};
