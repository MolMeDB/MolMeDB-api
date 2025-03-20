<?php

namespace Database\Factories;

use App\Models\Identifier;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Substance_identifier>
 */
class IdentifierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // $servers = Identifier::servers();
        $types = Identifier::types();
        $states = Identifier::states();

        return [
            'structure_id' => Structure::all()->random()->id,
            'source_id' => null,
            'source_type' => null,
            // 'server' => array_rand($servers),
            'type' => array_rand($types),
            'value' => fake()->uuid(),
            'state' => array_rand($states),
            'logs' => null
        ];
    }
}
