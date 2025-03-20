<?php

namespace Database\Seeders;

use Database\Factories\StructureFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Structure::factory(50)->create();

        // Save one by one because of identifier generators
        for($i = 0; $i < 100; $i++) {
            StructureFactory::new()->asChildren()->create();
        }
    }
}
