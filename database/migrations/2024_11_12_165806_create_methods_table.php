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
        Schema::create('methods', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->nullable();
            $table->integer('category_id')->nullable();
            $table->string('name', 150);
            $table->string('description')->nullable();
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('method_publication', function (Blueprint $table) {
            $table->integer('method_id');
            $table->integer('publication_id');

            $table->foreign('method_id')->references('id')->on('methods')->onDelete('cascade');
            $table->foreign('publication_id')->references('id')->on('publications')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('method_publication');
        Schema::dropIfExists('methods');
    }
};
