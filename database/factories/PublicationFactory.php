<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\References\EuropePMC\Enums\Sources;

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
            'identifier' => fake()->text(5),
            'identifier_source' => Sources::MED->value,
            'title' => fake()->text(50),
            'journal' => fake()->text(10),
            'volume' => fake()->text(5),
            'issue' => fake()->text(5),
            'page' => random_int(1,1000),
            'published_at' => fake()->date(),
            'validated_at' => fake()->date(),
        ];
    }
}
