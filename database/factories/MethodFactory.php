<?php

namespace Database\Factories;

use App\Models\Method;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Method>
 */
class MethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = array_keys(Method::types());

        return [
            'type' => $types[array_rand($types)],
            'name' => fake()->text(10),
            'abbreviation' => fake()->text(5),
            'description' => fake()->text(256)
        ];
    }
}
