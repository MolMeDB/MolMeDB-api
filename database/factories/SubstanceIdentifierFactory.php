<?php

namespace Database\Factories;

use App\Models\Substance;
use App\Models\SubstanceIdentifier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Substance_identifier>
 */
class SubstanceIdentifierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $servers = SubstanceIdentifier::servers();
        $types = SubstanceIdentifier::types();
        $states = SubstanceIdentifier::states();

        return [
            'substance_id' => Substance::all()->random()->id,
            'parent_id' => null,
            'server' => array_rand($servers),
            'type' => array_rand($types),
            'value' => fake()->uuid(),
            'user_id' => User::all()->random()->id,
            'state' => array_rand($states),
            'is_active' => fake()->boolean()
        ];
    }
}
