<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InteractionPassive>
 */
class InteractionPassiveFactory extends Factory
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
            'publication_id' => \App\Models\Publication::all()->random()->id,
            'temperature' => fake()->randomFloat(1, 25, 39),
            'ph' => fake()->randomFloat(1, 4, 9),
            'charge' => fake()->numberBetween(-2, 3),
            'note' => fake()->realText(50),
            'x_min' => fake()->randomFloat(2, 0, 8),
            'x_min_accuracy' => fake()->randomFloat(2, 0, 0.9),
            'gpen' => fake()->randomFloat(2, 0, 8),
            'gpen_accuracy' => fake()->randomFloat(2, 0, 0.9),
            'gwat' => fake()->randomFloat(2, 0, 8),
            'gwat_accuracy' => fake()->randomFloat(2, 0, 0.9),
            'logk' => fake()->randomFloat(2, 0, 8),
            'logk_accuracy' => fake()->randomFloat(2, 0, 0.9),
            'logperm' => fake()->randomFloat(2, 0, 8),
            'logperm_accuracy' => fake()->randomFloat(2, 0, 0.9),
        ];
    }
}
