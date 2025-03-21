<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('structures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('structures')->onDelete('restrict');
            $table->string('identifier', 20)->nullable()->unique('structure_identifier_idx');
            $table->unique('identifier');
            $table->string('canonical_smiles', 4000)->nullable();
            $table->integer('charge')->nullable();
            $table->double('ph_start')->nullable();
            $table->double('ph_end')->nullable();
            $table->string('inchi', 4000)->nullable();
            $table->string('inchikey', 27)->nullable();
            $table->double('molecular_weight')->nullable();
            $table->double('logp')->nullable();
            $table->text('molfile_3d')->nullable();
            $table->timestamps();
            $table->softDeletesDatetime();
        });

        Schema::create('identifiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('structure_id');
            $table->foreign('structure_id')->references('id')->on('structures')->onDelete('cascade');
            $table->string('value', 64)->index();
            $table->tinyInteger('type');
            $table->tinyInteger('state');
            $table->bigInteger('source_id')->nullable();
            $table->string('source_type', 255)->nullable();
            $table->json('logs')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type');
            $table->string('mime', 30)->nullable();
            $table->binary('content')->nullable();
            $table->string('name', 30)->nullable();
            $table->string('path')->nullable();
            $table->string('hash', 32);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('model_has_files', function (Blueprint $table) {
            $table->integer('file_id');
            $table->integer('model_id');
            $table->string('model_type', 255);
            $table->index(['model_id', 'model_type'], 'model_has_files_model_id_index');
            $table->foreign('file_id')
                ->references('id')
                ->on('files')
                ->cascadeOnDelete();
        });

        Schema::create('dataset_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('comment')->nullable();
            $table->timestamps(); 
        });

        Schema::create('datasets', function(Blueprint $table) {
            $table->id();
            $table->tinyInteger('type');
            $table->string('name', 255);
            $table->text('comment')->nullable();
            $table->integer('membrane_id')->nullable();
            $table->foreign('membrane_id')->references('id')->on('membranes')->onDelete('restrict');
            $table->integer('method_id')->nullable();
            $table->foreign('method_id')->references('id')->on('methods')->onDelete('restrict');
            $table->integer('dataset_group_id')->nullable();
            $table->foreign('dataset_group_id')->references('id')->on('dataset_groups')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('upload_queue', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type');
            $table->tinyInteger('state');
            $table->json('config')->nullable();
            $table->integer('file_id');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('restrict');
            $table->integer('dataset_id');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->json('logs')->nullable();
            $table->timestamps(); 
        });


       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_queue');
        Schema::dropIfExists('datasets');
        Schema::dropIfExists('dataset_groups');
        Schema::dropIfExists('model_has_files');
        Schema::dropIfExists('files');
        Schema::dropIfExists('identifiers');
        Schema::dropIfExists('structures');
    }
};
