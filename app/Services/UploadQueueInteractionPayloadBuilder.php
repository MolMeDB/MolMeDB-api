<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\Identifier;
use App\Models\Protein;
use App\Models\Publication;
use App\Models\Structure;
use App\Models\UploadQueue;
use Modules\Rdkit\Rdkit;
use Modules\References\EuropePMC\Enums\Sources;
use RuntimeException;

class UploadQueueInteractionPayloadBuilder
{
    private const DEFAULT_ACTIVE_CATEGORY_TITLE = 'Unassigned';

    public function __construct(
        private readonly UploadQueueColumnRegistry $columns,
        private readonly PublicationReferenceResolver $publicationResolver,
    ) {}

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    public function passivePayload(UploadQueue $record, array $row, bool $createMissingRecords = true): array
    {
        return [
            'dataset_id' => $record->dataset_id,
            'structure_id' => $this->resolveStructureId($row, $record, $createMissingRecords),
            'publication_id' => $this->resolvePublicationId($record, $row, $createMissingRecords),
            ...$this->interactionValues($row, $this->columns->commonValueColumns()),
            ...$this->interactionValues($row, $this->columns->interactionValueColumns($record)),
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    public function activePayload(UploadQueue $record, array $row, bool $createMissingRecords = true): array
    {
        $payload = [
            'dataset_id' => $record->dataset_id,
            'structure_id' => $this->resolveStructureId($row, $record, $createMissingRecords),
            'protein_id' => $this->resolveProteinId($row, $createMissingRecords),
            'publication_id' => $this->resolvePublicationId($record, $row, $createMissingRecords),
            ...$this->interactionValues($row, $this->columns->commonValueColumns()),
            ...$this->interactionValues($row, $this->columns->interactionValueColumns($record)),
        ];

        if ($createMissingRecords) {
            $payload['category_id'] = $this->defaultActiveCategoryId();
        }

        return $payload;
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<int|string, string>  $columns
     * @return array<string, mixed>
     */
    public function interactionValues(array $row, array $columns): array
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

    public function normalizeInteractionValue(string $column, string $value): float|string
    {
        $value = trim($value);

        if (in_array($column, ['charge', 'note'], true)) {
            return $value;
        }

        return round((float) str_replace(',', '.', $value), 2);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveStructureId(array $row, ?UploadQueue $record, bool $createMissingStructure): ?int
    {
        $structure = null;
        $smiles = trim($row['smiles'] ?? '');

        if ($smiles !== '') {
            $rdkit = new Rdkit;
            $canonicalSmiles = $rdkit->canonize_smiles($smiles);
            if (! $canonicalSmiles) {
                throw new RuntimeException('Unable to canonize SMILES for imported row. Check RDKit service availability and SMILES validity.');
            }

            $smiles = $canonicalSmiles;
        }

        if ($smiles !== '') {
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

        if (! $structure && $smiles === '') {
            throw new RuntimeException('Unable to resolve structure for imported row. Fill SMILES column or provide at least one valid structure identifier (PubChem CID, ChEMBL ID, PDB ID, DrugBank ID, ChEBI ID).');
        }

        if (! $structure && $createMissingStructure) {
            $structure = Structure::create([
                'canonical_smiles' => trim($smiles),
            ]);

            $structure->identifiers()->createMany(array_map(
                fn (string $column): array => [
                    'type' => $identifierColumns[$column],
                    'value' => trim($row[$column]),
                    'state' => Identifier::STATE_NEW,
                    'source_id' => $record?->dataset_id,
                    'source_type' => $record ? Dataset::class : null,
                ],
                array_filter(array_keys($identifierColumns), fn (string $column): bool => ! empty($row[$column] ?? null))
            ));
        }

        return $structure ? (int) $structure->id : null;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveProteinId(array $row, bool $createMissingProtein): ?int
    {
        $target = trim($row['active_target'] ?? '');
        if ($target === '') {
            throw new RuntimeException('Unable to resolve protein: target column is empty.');
        }

        $protein = Protein::withTrashed()
            ->whereRaw('LOWER(uniprot_id) = ?', [mb_strtolower($target)])
            ->first();

        if (! $protein && $createMissingProtein) {
            $protein = Protein::create([
                'uniprot_id' => $target,
            ]);
        }

        return $protein ? (int) $protein->id : null;
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
    private function resolvePublicationId(UploadQueue $record, array $row, bool $createMissingPublication): ?int
    {
        $reference = $this->publicationResolver->normalizeReference($row['primaryReference'] ?? '');
        if ($reference !== '') {
            if (! $createMissingPublication) {
                return $this->findPublicationId($reference) ?? -1;
            }

            return (int) $this->publicationResolver->resolveOrCreatePublication($reference)->id;
        }

        $sourcePublicationId = $record->config['source_publication_id'] ?? null;

        return is_numeric($sourcePublicationId) ? (int) $sourcePublicationId : null;
    }

    private function findPublicationId(string $reference): ?int
    {
        $publication = Publication::query()
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

        return $publication ? (int) $publication->id : null;
    }
}
