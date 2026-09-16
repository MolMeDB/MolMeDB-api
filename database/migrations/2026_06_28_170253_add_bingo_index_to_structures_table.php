<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $bingoInstalled = DB::scalar(<<<'SQL'
            SELECT EXISTS (
                SELECT 1
                FROM pg_am
                WHERE amname = 'bingo_idx'
            )
            SQL);

        if (! $bingoInstalled) {
            throw new RuntimeException(
                'Bingo PostgreSQL is not installed. Run bingo_install.sql before migrating.',
            );
        }

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS structures_canonical_smiles_bingo_index
            ON structures USING bingo_idx (canonical_smiles bingo.molecule)
            WHERE canonical_smiles IS NOT NULL
                AND bingo.checkMolecule(canonical_smiles) IS NULL
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS structures_canonical_smiles_bingo_index');
        }
    }
};
