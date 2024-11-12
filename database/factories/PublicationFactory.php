<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Publication>
 */
class PublicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = array_keys(\App\Models\Publication::types());

        return [
            'type' => $types[array_rand($types)],
            'citation' => fake()->text(256),
            'doi' => fake()->text(50),
            'pmid' => fake()->text(5),
            'title' => fake()->text(50),
            'authors' => fake()->text(15),
            'journal' => fake()->text(10),
            'volume' => fake()->text(5),
            'issue' => fake()->text(5),
            'page' => random_int(1,1000),
            'publicated_date' => fake()->date(),
            'user_id' => User::all()->random()->id
        ];
    }
}
