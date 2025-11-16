<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserAdminSeeder::class,
            AuthorSeeder::class,
            PublicationSeeder::class,
            MembraneSeeder::class,
            MethodSeeder::class,
            CategorySeeder::class,
            StructureSeeder::class,
            IdentifierSeeder::class,
        ]);

        // For each publication join random two authors
        $publications = \App\Models\Publication::all();
        foreach($publications as $publication) {
            $publication->authors()->attach(Author::all()->random(2)->pluck('id'));
        }

        $this->call([
            DatasetSeeder::class,
            InteractionPassiveSeeder::class,
            ProteinSeeder::class,
            InteractionActiveSeeder::class
        ]);
    }
}
