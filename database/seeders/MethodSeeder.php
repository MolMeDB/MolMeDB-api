<?php

namespace Database\Seeders;

use App\Models\Publication;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Method::factory(3)->create();
        
        // Add links to references
        $all = \App\Models\Method::all();

        foreach($all as $method) {
            for($i = 1; $i < 3; $i++) {
                $method->publications()->attach(Publication::all()->random()->id);
            }
        }
    }
}
