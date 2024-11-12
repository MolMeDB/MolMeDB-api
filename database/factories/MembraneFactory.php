<?php

namespace Database\Factories;

use App\Models\Membrane;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Membrane>
 */
class MembraneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::all()->random();
        $types = array_keys(Membrane::types());

        return [
            'user_id' => $user->id,
            'type' => $types[array_rand($types)],
            'name' => fake()->text(10),
            'description' => fake()->text(256)
        ];
    }
}
