<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\Identifier;
use App\Models\InteractionActive;
use App\Models\InteractionPassive;
use App\Models\Protein;
use App\Models\Structure;
use App\Models\UploadQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Rdkit\Rdkit;
use RuntimeException;

class UploadQueueImporter
{
    private const IGNORE_COLUMN = 'ignore';

    private const DEFAULT_ACTIVE_CATEGORY_TITLE = 'Unassigned';

    /**
     * @return array<int, array<string, string>>
     */
    public function previewRows(UploadQueue $record, int $limit = 25): array
    {
        return $this->readMappedRows($record, $limit)['rows'];
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(UploadQueue $record): array
    {
        $result = $this->readMappedRows($record, 0);

        return [
            'mode' => 'waiting_for_admin_review',
            'record_id' => $record->id,
            'dataset_id' => $record->dataset_id,
            'type' => $record->type,
            'prepared_rows' => $result['prepared_rows'],
            'skipped_rows' => $result['skipped_rows'],
        ];
    }

    public function import(UploadQueue $record): array
    {
        if (! $record->config->detailedValidationPassed()) {
            throw new RuntimeException('Detailed validation must pass before importing upload data.');
        }

        return match ((int) $record->type) {
            UploadQueue::TYPE_PASSIVE_DATASET => $this->importPassiveInteractions($record),
            UploadQueue::TYPE_ACTIVE_DATASET => $this->importActiveInteractions($record),
            default => throw new RuntimeException('Unsupported upload queue type: '.$record->type),
        };
    }

    /**
     * This is the final passive interaction persistence path.
     *
     * TODO: decide whether duplicate rows should be skipped, updated, or inserted
     * as separate measurements.
     * TODO: decide whether missing structures should be created from SMILES here
     * or rejected earlier during detailed validation.
     *
     * @return array<string, mixed>
     */
    private function importPassiveInteractions(UploadQueue $record): array
    {
        $summary = [
            'mode' => 'passive_interactions_imported',
            'record_id' => $record->id,
            'dataset_id' => $record->dataset_id,
            'type' => $record->type,
            'created_rows' => 0,
            'skipped_rows' => 0,
            'sample_rows' => [],
        ];

        $result = DB::transaction(function () use ($record, &$summary): array {
            return $this->forEachMappedRow($record, function (array $row) use ($record, &$summary): void {
                $columns = app(UploadQueueColumnRegistry::class);
                $payload = [
                    'dataset_id' => $record->dataset_id,
                    'structure_id' => $this->resolveStructureId($row, $record),
                    'publication_id' => $this->resolvePublicationId($record, $row),
                    ...$this->interactionValues($row, $columns->commonValueColumns()),
                    ...$this->interactionValues($row, $columns->interactionValueColumns($record)),
                ];

                InteractionPassive::query()->create($payload);

                $summary['created_rows']++;
                if (count($summary['sample_rows']) < 5) {
                    $summary['sample_rows'][] = $payload;
                }
            });
        });

        $summary['skipped_rows'] = $result['skipped_rows'];

        return $summary;
    }

    /**
     * This is the final active interaction persistence path.
     *
     * Active uploads start in a default category and are classified later by admin.
     * TODO: decide whether missing proteins should be created or rejected.
     *
     * @return array<string, mixed>
     */
    private function importActiveInteractions(UploadQueue $record): array
    {
        $summary = [
            'mode' => 'active_interactions_imported',
            'record_id' => $record->id,
            'dataset_id' => $record->dataset_id,
            'type' => $record->type,
            'created_rows' => 0,
            'skipped_rows' => 0,
            'sample_rows' => [],
        ];

        $result = DB::transaction(function () use ($record, &$summary): array {
            return $this->forEachMappedRow($record, function (array $row) use ($record, &$summary): void {
                $columns = app(UploadQueueColumnRegistry::class);
                $payload = [
                    'dataset_id' => $record->dataset_id,
                    'structure_id' => $this->resolveStructureId($row, $record),
                    'protein_id' => $this->resolveProteinId($row),
                    'category_id' => $this->defaultActiveCategoryId(),
                    'publication_id' => $this->resolvePublicationId($record, $row),
                    ...$this->interactionValues($row, $columns->commonValueColumns()),
                    ...$this->interactionValues($row, $columns->interactionValueColumns($record)),
                ];

                InteractionActive::query()->create($payload);

                $summary['created_rows']++;
                if (count($summary['sample_rows']) < 5) {
                    $summary['sample_rows'][] = $payload;
                }
            });
        });

        $summary['skipped_rows'] = $result['skipped_rows'];

        return $summary;
    }

    /**
     * @param  callable(array<string, string>): void  $callback
     * @return array{prepared_rows: int, skipped_rows: int, rows: array<int, array<string, string>>}
     */
    private function forEachMappedRow(UploadQueue $record, callable $callback): array
    {
        return $this->readMappedRows($record, 0, $callback);
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<int|string, string>  $columns
     * @return array<string, mixed>
     */
    private function interactionValues(array $row, array $columns): array
    {
        $values = [];

        foreach ($columns as $source => $target) {
            if (is_int($source)) {
                $source = $target;
            }

            $value = $row[$source] ?? null;
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $values[$target] = $this->normalizeInteractionValue($target, $value);
        }

        return $values;
    }

    private function normalizeInteractionValue(string $column, string $value): float|string
    {
        $value = trim($value);

        if (in_array($column, ['charge', 'note'], true)) {
            return $value;
        }

        return (float) str_replace(',', '.', $value);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveStructureId(array $row, ?UploadQueue $record): int
    {
        $structure = null;

        $smiles = trim($row['smiles'] ?? '');

        if ($smiles !== '') {
            $rdkit = new Rdkit;
            $canonical_smiles = $rdkit->canonize_smiles($smiles);
            if ($canonical_smiles) {
                $smiles = $canonical_smiles;
            }
        }

        if (! empty($smiles)) {
            $structure = Structure::withTrashed()
                ->where('canonical_smiles', $smiles)
                ->first();
        }

        $identifierColumns = [
            'pubchem' => Identifier::TYPE_PUBCHEM,
            'chembl' => Identifier::TYPE_CHEMBL,
            'pdb' => Identifier::TYPE_PDB,
            'drugbank' => Identifier::TYPE_DRUGBANK,
            'chebi' => Identifier::TYPE_CHEBI,
            'name' => Identifier::TYPE_NAME,
        ];

        foreach ($identifierColumns as $column => $type) {
            if ($structure || ! isset($row[$column])) {
                continue;
            }

            $identifier = Identifier::withTrashed()
                ->where('type', $type)
                ->where('value', trim($row[$column]))
                ->first();

            $structure = $identifier?->structure;
        }

        if (! $structure && empty($smiles ?? null)) {
            throw new RuntimeException('Unable to resolve structure for imported row. Fill SMILES column or provide at least one valid structure identifier (PubChem CID, ChEMBL ID, PDB ID, DrugBank ID, ChEBI ID).');
        }

        if (! $structure) {
            // If no existing structure could be resolved, create a new one from the provided SMILES.
            $structure = Structure::create([
                'canonical_smiles' => trim($smiles),
            ]);

            $structure->identifiers()->createMany(array_map(
                fn (string $column) => [
                    'type' => $identifierColumns[$column],
                    'value' => trim($row[$column]),
                    'state' => Identifier::STATE_NEW,
                    'source_id' => $record?->dataset_id,
                    'source_type' => $record ? Dataset::class : null,
                ],
                array_filter(array_keys($identifierColumns), fn (string $column) => ! empty($row[$column] ?? null))
            ));
        }

        return (int) $structure->id;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveProteinId(array $row): int
    {
        $target = trim($row['uniprot'] ?? '');
        if ($target === '') {
            throw new RuntimeException('Unable to resolve protein: uniprot_id column is empty.');
        }

        $protein = Protein::withTrashed()
            ->whereRaw('LOWER(uniprot_id) = ?', [mb_strtolower($target)])
            ->first();

        if (! $protein) {
            // Add new record to proteins table if it does not exist, to maintain referential integrity.
            $protein = Protein::create([
                'uniprot_id' => $target,
            ]);
        }

        return (int) $protein->id;
    }

    private function defaultActiveCategoryId(): int
    {
        return (int) Category::query()
            ->firstOrCreate([
                'title' => self::DEFAULT_ACTIVE_CATEGORY_TITLE,
                'type' => Category::TYPE_ACTIVE_INTERACTION,
            ], [
                'parent_id' => -1,
                'order' => 0,
            ])
            ->id;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolvePublicationId(UploadQueue $record, array $row): ?int
    {
        $resolver = app(PublicationReferenceResolver::class);
        $reference = $resolver->normalizeReference($row['primaryReference'] ?? '');
        if ($reference !== '') {
            return (int) $resolver->resolveOrCreatePublication($reference)->id;
        }

        $sourcePublicationId = $record->config['source_publication_id'] ?? null;

        return is_numeric($sourcePublicationId) ? (int) $sourcePublicationId : null;
    }

    /**
     * @return array{prepared_rows: int, skipped_rows: int, rows: array<int, array<string, string>>}
     */
    private function readMappedRows(UploadQueue $record, int $sampleLimit, ?callable $onRow = null): array
    {
        $disk = $record->file?->storage;
        if (! is_string($disk) || trim($disk) === '') {
            throw new RuntimeException('Uploaded file storage is not configured.');
        }

        $path = $record->file?->path;
        if (! is_string($path) || trim($path) === '' || ! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException('Uploaded file is missing on storage.');
        }

        $stream = Storage::disk($disk)->readStream($path);
        if (! $stream) {
            throw new RuntimeException('Cannot open uploaded file for import.');
        }

        $separator = $this->normalizeSeparator($record->config->separator());
        $attributes = $record->config->attributes();
        $skipFirstRow = $record->config->skipFirstRow() === 1;
        $preparedRows = 0;
        $skippedRows = 0;
        $lineNumber = 0;
        $sampleRows = [];

        try {
            while (($line = fgets($stream)) !== false) {
                $lineNumber++;
                $line = trim(mb_convert_encoding($line, 'UTF-8', 'auto'));

                if ($line === '') {
                    continue;
                }

                if ($skipFirstRow && $lineNumber === 1) {
                    continue;
                }

                $mappedRow = $this->mapRow(str_getcsv($line, $separator), $attributes);
                if ($mappedRow === []) {
                    $skippedRows++;

                    continue;
                }

                $preparedRows++;
                if ($onRow) {
                    $onRow($mappedRow);
                }

                if ($sampleLimit > 0 && count($sampleRows) < $sampleLimit) {
                    $sampleRows[] = $mappedRow;
                }
            }
        } finally {
            fclose($stream);
        }

        return [
            'prepared_rows' => $preparedRows,
            'skipped_rows' => $skippedRows,
            'rows' => $sampleRows,
        ];
    }

    private function normalizeSeparator(string $separator): string
    {
        if ($separator === '\\t' || mb_strtolower($separator) === 'tab') {
            return "\t";
        }

        return in_array($separator, [',', ';', "\t"], true) ? $separator : ',';
    }

    /**
     * @param  array<int, string|null>  $attributes
     * @return array<string, string>
     */
    private function mapRow(array $values, array $attributes): array
    {
        $mappedRow = [];

        foreach ($attributes as $index => $columnKey) {
            if (! is_string($columnKey) || $columnKey === self::IGNORE_COLUMN) {
                continue;
            }

            $value = $values[$index] ?? null;
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $mappedRow[$columnKey] = trim($value);
        }

        return $mappedRow;
    }
}
