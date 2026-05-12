<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Category;
use App\Models\Dataset;
use App\Models\Identifier;
use App\Models\InteractionActive;
use App\Models\InteractionPassive;
use App\Models\Protein;
use App\Models\Publication;
use App\Models\Structure;
use App\Models\UploadQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\References\CrossRef\CrossRef;
use Modules\References\EuropePMC\Enums\Sources;
use Modules\References\EuropePMC\EuropePMC;
use Modules\References\Models\Record;
use RuntimeException;
use Throwable;

class UploadQueueImporter
{
    private const IGNORE_COLUMN = 'ignore';

    private const DEFAULT_ACTIVE_CATEGORY_TITLE = 'Unassigned';

    private const PASSIVE_VALUE_COLUMNS = [
        'x_min',
        'x_min_acc' => 'x_min_accuracy',
        'gpen',
        'gpen_acc' => 'gpen_accuracy',
        'gwat',
        'gwat_acc' => 'gwat_accuracy',
        'logk',
        'logk_acc' => 'logk_accuracy',
        'logperm',
        'logperm_acc' => 'logperm_accuracy',
    ];

    private const ACTIVE_VALUE_COLUMNS = [
        'km',
        'km_acc' => 'km_accuracy',
        'ec50',
        'ec50_acc' => 'ec50_accuracy',
        'ki',
        'ki_acc' => 'ki_accuracy',
        'ic50',
        'ic50_acc' => 'ic50_accuracy',
    ];

    private const COMMON_VALUE_COLUMNS = [
        'temperature',
        'ph',
        'charge',
        'comment' => 'note',
    ];

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
                $payload = [
                    'dataset_id' => $record->dataset_id,
                    'structure_id' => $this->resolveStructureId($row, $record),
                    'publication_id' => $this->resolvePublicationId($record, $row),
                    ...$this->interactionValues($row, self::COMMON_VALUE_COLUMNS),
                    ...$this->interactionValues($row, self::PASSIVE_VALUE_COLUMNS),
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
                $payload = [
                    'dataset_id' => $record->dataset_id,
                    'structure_id' => $this->resolveStructureId($row, $record),
                    'protein_id' => $this->resolveProteinId($row),
                    'category_id' => $this->defaultActiveCategoryId(),
                    'publication_id' => $this->resolvePublicationId($record, $row),
                    ...$this->interactionValues($row, self::COMMON_VALUE_COLUMNS),
                    ...$this->interactionValues($row, self::ACTIVE_VALUE_COLUMNS),
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

        if (($row['smiles'] ?? null) !== null) {
            $structure = Structure::withTrashed()
                ->where('canonical_smiles', trim($row['smiles']))
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

        if (! $structure && empty($row['smiles'] ?? null)) {
            throw new RuntimeException('Unable to resolve structure for imported row. Fill SMILES column or provide at least one valid structure identifier (PubChem CID, ChEMBL ID, PDB ID, DrugBank ID, ChEBI ID).');
        }

        if (! $structure) {
            // If no existing structure could be resolved, create a new one from the provided SMILES.
            $structure = Structure::create([
                'canonical_smiles' => trim($row['smiles']),
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
        $reference = $this->normalizeReference($row['primaryReference'] ?? '');
        if ($reference !== '') {
            $publication = $this->findPublicationByReference($reference);
            if ($publication) {
                return (int) $publication->id;
            }

            $referenceRecord = $this->fetchPublicationRecord($reference);
            if (! $referenceRecord) {
                throw new RuntimeException("Unable to resolve publication reference '{$reference}'.");
            }

            $publication = $this->findPublicationByRecord($referenceRecord);
            if ($publication) {
                return (int) $publication->id;
            }

            return (int) $this->createPublicationFromReferenceRecord($referenceRecord, $reference)->id;
        }

        $sourcePublicationId = $record->config['source_publication_id'] ?? null;

        return is_numeric($sourcePublicationId) ? (int) $sourcePublicationId : null;
    }

    private function normalizeReference(string $reference): string
    {
        $reference = trim($reference);
        $reference = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $reference) ?? $reference;
        $reference = preg_replace('/^doi:\s*/i', '', $reference) ?? $reference;

        return trim($reference);
    }

    private function findPublicationByReference(string $reference): ?Publication
    {
        return Publication::query()
            ->where(function ($query) use ($reference): void {
                $query
                    ->where(function ($query) use ($reference): void {
                        $query
                            ->where('identifier_source', Sources::MED->value)
                            ->where('identifier', $reference);
                    })
                    ->orWhereRaw('LOWER(doi) = ?', [mb_strtolower($reference)]);
            })
            ->first();
    }

    private function findPublicationByIdentifier(string $identifier, Sources $source): ?Publication
    {
        return Publication::query()
            ->where('identifier_source', $source->value)
            ->where('identifier', $identifier)
            ->first();
    }

    private function findPublicationByRecord(Record $record): ?Publication
    {
        if (is_string($record->id) && trim($record->id) !== '' && $record->source instanceof Sources) {
            $publication = $this->findPublicationByIdentifier(trim($record->id), $record->source);
            if ($publication) {
                return $publication;
            }
        }

        if (is_string($record->doi) && trim($record->doi) !== '') {
            return Publication::query()
                ->whereRaw('LOWER(doi) = ?', [mb_strtolower(trim($record->doi))])
                ->first();
        }

        return null;
    }

    private function fetchPublicationRecord(string $reference): ?Record
    {
        if (ctype_digit($reference)) {
            return $this->fetchEuropePmcRecord($reference, Sources::MED);
        }

        $record = $this->fetchCrossRefRecord($reference);
        if ($record) {
            return $record;
        }

        return $this->searchEuropePmcRecordByDoi($reference);
    }

    private function fetchEuropePmcRecord(string $identifier, Sources $source): ?Record
    {
        try {
            return (new EuropePMC)->detail($identifier, $source);
        } catch (Throwable) {
            return null;
        }
    }

    private function fetchCrossRefRecord(string $doi): ?Record
    {
        try {
            return (new CrossRef)->work($doi);
        } catch (Throwable) {
            return null;
        }
    }

    private function searchEuropePmcRecordByDoi(string $doi): ?Record
    {
        try {
            $result = (new EuropePMC)->search('DOI:"'.$doi.'"', pageSize: 1);
        } catch (Throwable) {
            return null;
        }

        $records = is_array($result) ? ($result['records'] ?? []) : [];
        $record = $records[0] ?? null;

        return $record instanceof Record ? $record : null;
    }

    private function createPublicationFromReferenceRecord(Record $record, string $fallbackIdentifier): Publication
    {
        $doi = $record->doi ? trim($record->doi) : null;
        $identifier = $record->id ? trim($record->id) : (ctype_digit($fallbackIdentifier) ? $fallbackIdentifier : null);
        $identifierSource = $record->source?->value ?? (ctype_digit($fallbackIdentifier) ? Sources::MED->value : null);

        $publication = Publication::query()->create([
            'citation' => Str::limit(
                $record->citation() ?: ($record->title ?? $doi ?? 'Unknown citation'),
                1024
            ),
            'title' => $record->title ? Str::limit($record->title, 512) : null,
            'doi' => $doi ?? (! ctype_digit($fallbackIdentifier) ? $fallbackIdentifier : null),
            'identifier' => $identifier,
            'identifier_source' => $identifierSource,
            'journal' => $record->journal?->title,
            'volume' => $record->journal?->volume,
            'issue' => $record->journal?->issue,
            'page' => $record->pageInfo,
            'year' => is_numeric($record->journal?->yearOfPublication) ? (int) $record->journal?->yearOfPublication : null,
            'published_at' => $record->publicationDate(),
            'validated_at' => now(),
        ]);

        if (is_array($record->authors)) {
            foreach ($record->authors as $author) {
                $authorModel = Author::firstOrCreate([
                    'first_name' => $author->firstName,
                    'last_name' => $author->lastName,
                    'full_name' => $author->fullName,
                    'affiliation' => $author->affiliations && count($author->affiliations) > 0 ? $author->affiliations[0] : null,
                ]);

                $publication->authors()->syncWithoutDetaching([$authorModel->id]);
            }
        }

        return $publication->refresh();
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
