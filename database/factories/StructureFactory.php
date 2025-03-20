<?php

namespace Database\Factories;

use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Structure>
 */
class StructureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'identifier' => "MM" . fake()->numberBetween(1, 1000000),
            'canonical_smiles' => fake()->text(10),
            'charge' => fake()->numberBetween(-2,2),
            'ph_start' => fake()->randomFloat(2,1,14),
            'ph_end' => fake()->randomFloat(2,1,14),
            'inchi' => fake()->text(20),
            'inchikey' => fake()->text(10),
            'molfile_3d' => null,
        ];
    }

    public function asChildren() 
    {
        return $this->state(function (array $attributes) {
            $parent = Structure::where('parent_id', null)->get()->random();
            return [
                'parent_id' => $parent->id,
                'identifier' => $parent->generateIdentifier(),
            ];
        }
        );
    }
}
