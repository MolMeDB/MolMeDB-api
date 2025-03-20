<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Membrane;
use App\Models\Method;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Category::factory(10)->create();

        // Assign to membranes and methods
        $mems = \App\Models\Membrane::all();
        $mets = \App\Models\Method::all();

        foreach($mems as $mem) {
            $mem->categories()->attach(
                \App\Models\Category::where('type', Category::TYPE_MEMBRANE)
                    ->get()
                    ->random()
                    ->id, 
                [
                    'model_type' => Membrane::class
                ]);
        }

        foreach($mets as $met) {
            $met->categories()->attach(
                \App\Models\Category::where('type', Category::TYPE_METHOD)
                    ->get()
                    ->random()
                    ->id, 
                [
                    'model_type' => Method::class
                ]);
        }
    }
}
