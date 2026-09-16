<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'predictions';

    public function up(): void
    {
        Schema::connection($this->connection)->table('structures', function (Blueprint $table): void {
            $table->string('remote_molecule_status', 50)->nullable()->after('canonical_smiles');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('structures', function (Blueprint $table): void {
            $table->dropColumn('remote_molecule_status');
        });
    }
};
