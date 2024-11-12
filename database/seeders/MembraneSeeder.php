<?php

namespace Database\Seeders;

use App\Models\Publication;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MembraneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Membrane::factory(3)->create();

        // Add links to references
        $all = \App\Models\Membrane::all();

        foreach($all as $membrane) {
            for($i = 1; $i < 3; $i++) {
                $membrane->publications()->attach(Publication::all()->random()->id);
            }
        }
    }
}
