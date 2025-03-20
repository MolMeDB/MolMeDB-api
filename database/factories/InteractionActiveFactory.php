<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InteractionActive>
 */
class InteractionActiveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dataset_id' => \App\Models\Dataset::all()->random()->id,
            'structure_id' => \App\Models\Structure::all()->random()->id,
            'protein_id' => \App\Models\Protein::all()->random()->id,
            'publication_id' => \App\Models\Publication::all()->random()->id,
            'type' => fake()->randomElement(array_keys(\App\Models\InteractionActive::enumType())),
            'temperature' => fake()->randomFloat(1, 25, 39),
            'ph' => fake()->randomFloat(1, 4, 9),
            'charge' => fake()->numberBetween(-2, 3),
            'note' => fake()->realText(50),
            'km' => fake()->randomFloat(1, 0.5, 8),
            'km_accuracy' => fake()->randomFloat(1, 0, 0.9),
            'ec50' => fake()->randomFloat(1, 0.5, 8),
            'ec50_accuracy' => fake()->randomFloat(1, 0, 0.9),
            'ki' => fake()->randomFloat(1, 0.5, 8),
            'ki_accuracy' => fake()->randomFloat(1, 0, 0.9),
            'ic50' => fake()->randomFloat(1, 0.5, 8),
            'ic50_accuracy' => fake()->randomFloat(1, 0, 0.9),
        ];
    }
}
