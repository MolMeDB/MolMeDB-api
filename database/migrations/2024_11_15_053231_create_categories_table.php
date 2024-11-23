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
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->integer('order')->default(0)->index();
            $table->string('title', 256);
            $table->integer('type');
            $table->string('content')->nullable();
            $table->string('data')->nullable();
            $table->string('regexp', 256)->nullable();
            $table->timestamps();
        });

        Schema::create('category_models', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->integer('model_id');
            $table->string('model', 256); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_models');
        Schema::dropIfExists('categories');
    }
};
