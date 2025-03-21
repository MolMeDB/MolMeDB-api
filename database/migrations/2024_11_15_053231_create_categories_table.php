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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id')->nullable()->default(-1);
            $table->integer('order')->default(0)->index();
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->tinyInteger('type');
            $table->timestamps();
        });

        Schema::create('model_has_categories', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->integer('model_id');
            $table->string('model_type', 255); 
            $table->index(['model_id', 'model_type'], 'model_has_categories_model_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_categories');
        Schema::dropIfExists('categories');
    }
};
