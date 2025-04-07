<?php

use App\Models\ProteinIdentifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('protein_identifiers', function (Blueprint $table) {
            $table->id();
            $table->integer('protein_id');
            $table->foreign('protein_id')->references('id')->on('proteins')->onDelete('cascade');
            $table->string('value', 255)->index();
            $table->tinyInteger('type');
            $table->tinyInteger('state')->nullable()->default(null);
            $table->integer('source_id')->nullable();
            $table->string('source_type', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('proteins')->select('id', 'name')->chunkById(100, function ($proteins) {
            foreach ($proteins as $protein) {
                $source = DB::table('activity_log')
                    ->where('subject_type', 'App\\Models\\Protein')
                    ->where('subject_id', $protein->id)
                    ->orderBy('created_at', 'asc');

                DB::table('protein_identifiers')->insert([
                    'protein_id' => $protein->id,
                    'value' => $protein->name,
                    'type'  => ProteinIdentifier::TYPE_NAME,
                    'state' => ProteinIdentifier::STATE_VALIDATED,
                    'source_id' => $source->first()?->causer_id,
                    'source_type' => $source->first()?->causer_type,
                    'created_at' => $source->first()?->created_at,
                    'updated_at' => $source->first()?->created_at
                ]);
            }
        });

        Schema::table('proteins', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protein_identifiers');
        Schema::table('proteins', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
    }
};
