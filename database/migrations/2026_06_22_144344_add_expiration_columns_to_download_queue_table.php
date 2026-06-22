<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('download_queue', function (Blueprint $table) {
            $table->string('selection_hash', 64)->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('files_deleted_at')->nullable();
        });

        DB::table('download_queue')
            ->select(['id', 'selection', 'created_at'])
            ->orderBy('id')
            ->chunkById(500, function ($downloads): void {
                foreach ($downloads as $download) {
                    $selection = is_string($download->selection)
                        ? json_decode($download->selection, true)
                        : (array) $download->selection;
                    $normalizedSelection = $this->normalizeSelection($selection ?: []);

                    DB::table('download_queue')
                        ->where('id', $download->id)
                        ->update([
                            'selection_hash' => hash('sha256', json_encode($normalizedSelection, JSON_THROW_ON_ERROR)),
                            'expires_at' => Carbon::parse($download->created_at)->addDays(2),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('download_queue', function (Blueprint $table) {
            $table->dropColumn([
                'selection_hash',
                'expires_at',
                'files_deleted_at',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $selection
     * @return array<string, array<int, int|string>>
     */
    private function normalizeSelection(array $selection): array
    {
        $membraneIds = array_values(array_unique(array_map('intval', $selection['membrane_ids'] ?? [])));
        $methodIds = array_values(array_unique(array_map('intval', $selection['method_ids'] ?? [])));
        $proteinIds = array_values(array_unique(array_map('intval', $selection['protein_ids'] ?? [])));
        $structureIdentifiers = array_values(array_unique(array_filter(array_map(
            static fn (mixed $identifier): string => trim((string) $identifier),
            $selection['structure_identifiers'] ?? [],
        ))));

        sort($membraneIds, SORT_NUMERIC);
        sort($methodIds, SORT_NUMERIC);
        sort($proteinIds, SORT_NUMERIC);
        sort($structureIdentifiers, SORT_STRING);

        return [
            'membrane_ids' => $membraneIds,
            'method_ids' => $methodIds,
            'protein_ids' => $proteinIds,
            'structure_identifiers' => $structureIdentifiers,
        ];
    }
};
