<?php

namespace Database\Factories;

use App\Models\Dataset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dataset>
 */
class DatasetFactory extends Factory
{
    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Dataset $dataset) {
            // ...
        })->afterCreating(function (Dataset $dataset) {
            // Add publications
            $dataset->publications()->attach(\App\Models\Publication::all()->random()->id, [
                'model_type' => Dataset::class
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(array_keys(\App\Models\Dataset::enumType())),
            'name' => fake()->streetName(),
            'comment' => fake()->realText(150),
            'membrane_id' => \App\Models\Membrane::all()->random()->id,
            'method_id' => \App\Models\Method::all()->random()->id,
            'dataset_group_id' => \App\Models\DatasetGroup::all()->random()->id,
        ];
    }
}
