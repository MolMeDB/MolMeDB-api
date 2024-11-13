<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubstanceIdentifierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $substances = \App\Models\Substance::all();

        foreach ($substances as $substance) {
            \App\Models\SubstanceIdentifier::factory(4)->create([
                'substance_id' => $substance->id
            ]);
        }

        

    }
}
