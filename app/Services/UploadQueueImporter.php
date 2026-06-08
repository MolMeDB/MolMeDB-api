<?php

namespace App\Services;

use App\Models\InteractionActive;
use App\Models\InteractionPassive;
use App\Models\Structure;
use App\Models\UploadQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UploadQueueImporter
{
    private const IGNORE_COLUMN = 'ignore';

    public function __construct(
        private readonly UploadQueueInteractionPayloadBuilder $payloadBuilder,
        private readonly UploadQueueDuplicateInteractionChecker $duplicateChecker,
        private readonly UploadQueueColumnRegistry $columns,
        private readonly UploadQueueCsvParser $csvParser,
    ) {}

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
     * @return array<string, mixed>
     */
    private function importPassiveInteractions(UploadQueue $record): array
    {
        $summary = [
            'mode' => 'passive_interactions_imported',
            'record_id' => $record->id,
            'dataset_id' => $record->dataset_id,
            'type' => $record->type,
            'prepared_rows' => 0,
            'created_rows' => 0,
            'skipped_rows' => 0,
            'duplicate_rows' => 0,
            'duplicate_conflict_rows' => 0,
            'duplicate_warnings' => [],
            'duplicate_errors' => [],
            'sample_rows' => [],
        ];

        $importedStructureIds = [];

        $result = DB::transaction(function () use ($record, &$summary, &$importedStructureIds): array {
            return $this->forEachMappedRow($record, function (array $row) use ($record, &$summary, &$importedStructureIds): void {
                $payload = $this->payloadBuilder->passivePayload($record, $row);

                if ($this->skipDuplicate($record, $payload, $summary)) {
                    return;
                }

                InteractionPassive::query()->create($payload);

                $importedStructureIds[] = (int) $payload['structure_id'];
                $summary['created_rows']++;
                if (count($summary['sample_rows']) < 5) {
                    $summary['sample_rows'][] = $payload;
                }
            });
        });

        $summary['prepared_rows'] = $result['prepared_rows'];
        $summary['skipped_rows'] += $result['skipped_rows'];
        $this->failIfNothingWasImported($summary);
        $this->checkInternalIdentifiers($importedStructureIds, $summary);

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
            'prepared_rows' => 0,
            'created_rows' => 0,
            'skipped_rows' => 0,
            'duplicate_rows' => 0,
            'duplicate_conflict_rows' => 0,
            'duplicate_warnings' => [],
            'duplicate_errors' => [],
            'sample_rows' => [],
        ];

        $importedStructureIds = [];

        $result = DB::transaction(function () use ($record, &$summary, &$importedStructureIds): array {
            return $this->forEachMappedRow($record, function (array $row) use ($record, &$summary, &$importedStructureIds): void {
                $payload = $this->payloadBuilder->activePayload($record, $row);

                if ($this->skipDuplicate($record, $payload, $summary)) {
                    return;
                }

                InteractionActive::query()->create($payload);

                $importedStructureIds[] = (int) $payload['structure_id'];
                $summary['created_rows']++;
                if (count($summary['sample_rows']) < 5) {
                    $summary['sample_rows'][] = $payload;
                }
            });
        });

        $summary['prepared_rows'] = $result['prepared_rows'];
        $summary['skipped_rows'] += $result['skipped_rows'];
        $this->failIfNothingWasImported($summary);
        $this->checkInternalIdentifiers($importedStructureIds, $summary);

        return $summary;
    }

    /**
     * @param  array<int, int>  $structureIds
     * @param  array<string, mixed>  $summary
     */
    private function checkInternalIdentifiers(array $structureIds, array &$summary): void
    {
        $ids = collect($structureIds)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $summary['internal_identifier_rows'] = 0;

            return;
        }

        $missingIdentifierIds = Structure::query()
            ->whereIn('id', $ids)
            ->whereNull('identifier')
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        $summary['internal_identifier_rows'] = count($missingIdentifierIds);

        if ($missingIdentifierIds === []) {
            return;
        }

        $exitCode = Artisan::call('structures:check-internal-identifiers', [
            '--ids' => $missingIdentifierIds,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Imported interactions were saved, but internal structure identifiers could not be generated.');
        }
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
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $summary
     */
    private function skipDuplicate(UploadQueue $record, array $payload, array &$summary): bool
    {
        $duplicate = $this->duplicateChecker->check($record, $payload, $this->interactionValueColumns($record));
        if ($duplicate === null) {
            return false;
        }

        $summary['skipped_rows']++;

        if ($duplicate['status'] === 'same') {
            $summary['duplicate_rows']++;
            $summary['duplicate_warnings'][] = $this->duplicateWarningMessage($duplicate['existing_id']);

            return true;
        }

        $summary['duplicate_conflict_rows']++;
        $summary['duplicate_errors'][] = $this->duplicateConflictMessage($duplicate['existing_id'], $duplicate['differences']);

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function interactionValueColumns(UploadQueue $record): array
    {
        return array_values($this->columns->interactionValueColumns($record));
    }

    private function duplicateWarningMessage(int $existingId): string
    {
        return "Interaction already exists in database as ID {$existingId} with the same values. Row was skipped.";
    }

    /**
     * @param  array<string, array{database: mixed, upload: mixed}>  $differences
     */
    private function duplicateConflictMessage(int $existingId, array $differences): string
    {
        $values = collect($differences)
            ->map(fn (array $difference, string $column): string => "{$column}: DB={$difference['database']}, upload={$difference['upload']}")
            ->implode('; ');

        return "Interaction already exists in database as ID {$existingId}, but has different values after rounding to 2 decimals ({$values}). Row was skipped.";
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function failIfNothingWasImported(array $summary): void
    {
        if (($summary['prepared_rows'] ?? 0) > 0 && ($summary['created_rows'] ?? 0) === 0 && ($summary['skipped_rows'] ?? 0) >= ($summary['prepared_rows'] ?? 0)) {
            throw new RuntimeException('All upload rows were skipped. There is nothing to import.');
        }
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

        $separator = $this->csvParser->normalizeSeparator($record->config->separator());
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

                $mappedRow = $this->mapRow($this->csvParser->parseLine($line, $separator), $attributes);
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
