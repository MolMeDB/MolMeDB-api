<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@molmedb.cz',
            'password' => Hash::make('admin')
        ]);

        $this->call([
            // PublicationSeeder::class,
            // MembraneSeeder::class,
            // MethodSeeder::class,
            // SubstanceSeeder::class
        ]);
    }
}
