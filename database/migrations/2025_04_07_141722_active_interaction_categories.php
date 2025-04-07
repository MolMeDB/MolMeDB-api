<?php

use App\Models\Category;
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
        Schema::table('interactions_active', function (Blueprint $table) {
            $table->integer('category_id')->nullable()->after('id'); 
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
        });

        $toAdd = [
            1 => 'Inhibitor',
            2 => 'Substrate',
            3 => 'Non-substrate',
            4 => 'Non-inhibitor',
            5 => 'N/A',
            6 => 'Interacts',
            7 => 'Substrate + inhibitor',
            8 => 'Substrate + Noninhibitor',
            9 => 'Nonsubstate + inhibitor',
            10 => 'Nonsubtrate + noninhibitor',
            11 => 'Activator',
            12 => 'Agonist',
            13 => 'Antagonist'
        ];

        foreach ($toAdd as $type => $name) {
            $id = DB::table('categories')->insertGetId([
                'parent_id' => -1,
                'order' => $type,
                'title' => $name,
                'content' => null,
                'type' => Category::TYPE_ACTIVE_INTERACTION,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('interactions_active')->where('type', $type)->update(['category_id' => $id]);
        }

        Schema::table('interactions_active', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactions_active', function (Blueprint $table) {
            $table->dropColumn('category_id');
            $table->integer('type')->nullable()->after('id');
        });
    }
};
