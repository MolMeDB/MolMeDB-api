<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\References\EuropePMC\Enums\Sources;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->string('identifier_source', 20)->nullable()->after('pmid'); 
            $table->renameColumn('pmid', 'identifier');
            $table->renameColumn('publicated_date', 'published_at');
            $table->timestamp('validated_at')->nullable()->after('published_at');
        });

        // Set all identifier_source to PMID
        DB::table('publications')->whereNull('identifier_source')->update(['identifier_source' => Sources::MED]);

        Schema::table('authors', function (Blueprint $table) {
            $table->renameColumn('name', 'first_name');
            $table->string('last_name', 150)->nullable()->after('first_name');
            $table->string('full_name', 150)->nullable()->after('first_name');
            $table->string('affiliation', 512)->nullable()->after('full_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->renameColumn('first_name', 'name');
            $table->dropColumn('last_name');
            $table->dropColumn('affiliation_id');
            $table->dropColumn('affiliation');
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->dropColumn('validated_at');
            $table->renameColumn('published_at', 'publicated_date');
            $table->renameColumn('identifier', 'pmid');
            $table->dropColumn('identifier_source');
        });
    }
};
