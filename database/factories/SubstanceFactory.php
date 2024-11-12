<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Substance>
 */
class SubstanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' =>  fake()->text(10),//fake()->numberBetween(1, 100),
            // 'name' => fake()->text(10),
            // 'molecular_weight' => fake()->randomFloat(2,1,100),
            // 'logp' => fake()->randomFloat(2,1,10),
            // 'user_id' => User::all()->random()->id,
        ];
    }
}
