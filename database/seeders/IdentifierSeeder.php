<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IdentifierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $structures = \App\Models\Structure::all();

        foreach ($structures as $structure) {
            \App\Models\Identifier::factory(4)->create([
                'structure_id' => $structure->id
            ]);
        }

        

    }
}
