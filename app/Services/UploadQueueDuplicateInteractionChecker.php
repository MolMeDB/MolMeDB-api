<?php

namespace App\Services;

use App\Models\InteractionActive;
use App\Models\InteractionPassive;
use App\Models\UploadQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UploadQueueDuplicateInteractionChecker
{
    /**
     * Caches the DB candidate set per (type, structure_id, charge, publication_id[, protein_id])
     * combination so a repeated combination (e.g. same compound measured at several
     * temperatures/pH) does not issue a fresh query for every row.
     *
     * @var array<string, Collection<int, Model>>
     */
    private array $candidatesCache = [];

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $valueColumns
     * @return array{status: 'same'|'conflict', existing_id: int, differences: array<string, array{database: mixed, upload: mixed}>}|null
     */
    public function check(UploadQueue $record, array $payload, array $valueColumns): ?array
    {
        $existing = $this->findExistingInteraction($record, $payload);
        if (! $existing) {
            return null;
        }

        $comparison = $this->compareValues($existing, $payload, $valueColumns);
        if ($comparison['has_missing_database_value']) {
            return null;
        }

        return [
            'status' => $comparison['differences'] === [] ? 'same' : 'conflict',
            'existing_id' => (int) $existing->getKey(),
            'differences' => $comparison['differences'],
        ];
    }

    /**
     * Registers a freshly created interaction in the candidate cache so later rows
     * of the same import run see it as a duplicate without re-querying the database.
     *
     * @param  array<string, mixed>  $payload
     */
    public function remember(UploadQueue $record, Model $interaction, array $payload): void
    {
        if (($payload['structure_id'] ?? null) === null) {
            return;
        }

        if ($record->type === UploadQueue::TYPE_ACTIVE_DATASET && ($payload['protein_id'] ?? null) === null) {
            return;
        }

        $cacheKey = $this->candidatesCacheKey($record, $payload);
        $this->candidatesCache[$cacheKey] ??= collect();
        $this->candidatesCache[$cacheKey]->push($interaction);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findExistingInteraction(UploadQueue $record, array $payload): ?Model
    {
        if (($payload['structure_id'] ?? null) === null) {
            return null;
        }

        if ($record->type === UploadQueue::TYPE_ACTIVE_DATASET && ($payload['protein_id'] ?? null) === null) {
            return null;
        }

        $cacheKey = $this->candidatesCacheKey($record, $payload);

        if (! array_key_exists($cacheKey, $this->candidatesCache)) {
            $query = $record->type === UploadQueue::TYPE_ACTIVE_DATASET
                ? InteractionActive::query()
                : InteractionPassive::query();

            $this->whereNullable($query, 'structure_id', $payload['structure_id'] ?? null);
            $this->whereNullable($query, 'charge', $payload['charge'] ?? null);

            if (($payload['publication_id'] ?? null) !== -1) {
                $this->whereNullable($query, 'publication_id', $payload['publication_id'] ?? null);
            }

            if ($record->type === UploadQueue::TYPE_ACTIVE_DATASET) {
                $this->whereNullable($query, 'protein_id', $payload['protein_id'] ?? null);
            }

            $this->candidatesCache[$cacheKey] = $query->get();
        }

        return $this->candidatesCache[$cacheKey]
            ->first(fn (Model $interaction): bool => $this->matchesRoundedSetting($interaction, $payload, 'temperature')
                && $this->matchesRoundedSetting($interaction, $payload, 'ph'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function candidatesCacheKey(UploadQueue $record, array $payload): string
    {
        $parts = [
            (string) $record->type,
            $this->keyPart($payload['structure_id'] ?? null),
            $this->keyPart($payload['charge'] ?? null),
            ($payload['publication_id'] ?? null) !== -1 ? $this->keyPart($payload['publication_id'] ?? null) : 'any',
        ];

        if ($record->type === UploadQueue::TYPE_ACTIVE_DATASET) {
            $parts[] = $this->keyPart($payload['protein_id'] ?? null);
        }

        return implode('|', $parts);
    }

    private function keyPart(mixed $value): string
    {
        return $value === null || $value === '' ? 'null' : (string) $value;
    }

    private function whereNullable(Builder $query, string $column, mixed $value): void
    {
        if ($value === null || $value === '') {
            $query->whereNull($column);

            return;
        }

        $query->where($column, $value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function matchesRoundedSetting(Model $interaction, array $payload, string $column): bool
    {
        $databaseValue = $interaction->{$column};
        $uploadValue = $payload[$column] ?? null;

        if ($databaseValue === null || $uploadValue === null || $databaseValue === '' || $uploadValue === '') {
            return $databaseValue === null && ($uploadValue === null || $uploadValue === '');
        }

        return round((float) $databaseValue, 2) === round((float) $uploadValue, 2);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $valueColumns
     * @return array<string, array{database: mixed, upload: mixed}>
     */
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $valueColumns
     * @return array{has_missing_database_value: bool, differences: array<string, array{database: mixed, upload: mixed}>}
     */
    private function compareValues(Model $existing, array $payload, array $valueColumns): array
    {
        $differences = [];
        $hasMissingDatabaseValue = false;

        foreach ($valueColumns as $column) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            $databaseValue = $existing->{$column};
            $uploadValue = $payload[$column];

            if (($databaseValue === null || $databaseValue === '') && $uploadValue !== null && $uploadValue !== '') {
                $hasMissingDatabaseValue = true;

                continue;
            }

            if ($this->sameValue($databaseValue, $uploadValue)) {
                continue;
            }

            $differences[$column] = [
                'database' => $this->normalizedComparableValue($databaseValue),
                'upload' => $this->normalizedComparableValue($uploadValue),
            ];
        }

        return [
            'has_missing_database_value' => $hasMissingDatabaseValue,
            'differences' => $differences,
        ];
    }

    private function sameValue(mixed $databaseValue, mixed $uploadValue): bool
    {
        if ($databaseValue === null || $uploadValue === null || $databaseValue === '' || $uploadValue === '') {
            return ($databaseValue === null || $databaseValue === '') && ($uploadValue === null || $uploadValue === '');
        }

        if (is_numeric($databaseValue) && is_numeric($uploadValue)) {
            return round((float) $databaseValue, 2) === round((float) $uploadValue, 2);
        }

        return (string) $databaseValue === (string) $uploadValue;
    }

    private function normalizedComparableValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        return $value;
    }
}
