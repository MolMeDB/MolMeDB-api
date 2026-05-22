<?php

namespace App\Services;

use App\Models\UploadQueue;
use App\Rules\UploadFile\ActiveInteractions\ColumnEc50;
use App\Rules\UploadFile\ActiveInteractions\ColumnIc50;
use App\Rules\UploadFile\ActiveInteractions\ColumnKi;
use App\Rules\UploadFile\ActiveInteractions\ColumnKm;
use App\Rules\UploadFile\ActiveInteractions\ColumnTarget;
use App\Rules\UploadFile\Identifiers\ColumnSmiles;
use App\Rules\UploadFile\PassiveInteractions\ColumnGpen;
use App\Rules\UploadFile\PassiveInteractions\ColumnGwat;
use App\Rules\UploadFile\PassiveInteractions\ColumnLogK;
use App\Rules\UploadFile\PassiveInteractions\ColumnLogPerm;
use App\Rules\UploadFile\PassiveInteractions\ColumnXmin;
use App\ValueObjects\UploadQueueConfig;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UploadQueueDetailedValidator
{
    private const IGNORE_COLUMN = 'ignore';

    public function __construct(
        private readonly UploadQueueInteractionPayloadBuilder $payloadBuilder,
        private readonly UploadQueueDuplicateInteractionChecker $duplicateChecker,
        private readonly UploadQueueColumnRegistry $columns,
    ) {}

    public function validate(UploadQueue $record): array
    {
        $disk = $record->file?->storage;
        if (! is_string($disk) || trim($disk) === '') {
            return [
                'ok' => false,
                'errors' => ['Uploaded file storage is not configured.'],
                'warnings' => [],
                'config' => null,
            ];
        }

        $path = $record->file?->path;
        if (! is_string($path) || $path === '' || ! Storage::disk($disk)->exists($path)) {
            return [
                'ok' => false,
                'errors' => ['Uploaded file was not found on storage.'],
                'warnings' => [],
                'config' => null,
            ];
        }

        $stream = Storage::disk($disk)->readStream($path);
        if (! $stream) {
            return [
                'ok' => false,
                'errors' => ['Cannot open uploaded file.'],
                'warnings' => [],
                'config' => null,
            ];
        }

        $rows = [];
        while (($line = fgets($stream)) !== false) {
            $line = mb_convert_encoding($line, 'UTF-8', 'auto');
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $rows[] = $line;
        }
        fclose($stream);

        $configured = $record->config->toArray();
        $hasManualConfig = is_array($configured['attributes'] ?? null) &&
            isset($configured['separator']) &&
            isset($configured['skip_first_row']);

        if (! $hasManualConfig && count($rows) < 2) {
            return [
                'ok' => false,
                'errors' => ['File must contain a header and at least one data row.'],
                'warnings' => [],
                'config' => null,
            ];
        }

        if ($hasManualConfig) {
            $separator = $this->normalizeSeparator((string) $configured['separator']);
            $columnKeys = array_map(function ($value) {
                if (! is_string($value) || trim($value) === '') {
                    return self::IGNORE_COLUMN;
                }

                return trim($value);
            }, $configured['attributes']);

            $skipFirstRow = (int) $configured['skip_first_row'] === 1 ? 1 : 0;
            $dataLines = $skipFirstRow === 1 ? array_slice($rows, 1) : $rows;
        } else {
            $separator = $this->detectSeparator($rows[0]);
            $headerRow = str_getcsv($rows[0], $separator);
            $columnKeys = $this->buildColumnMapping($record, $headerRow);
            $dataLines = array_slice($rows, 1);
        }

        if (count($dataLines) < 1) {
            return [
                'ok' => false,
                'errors' => ['File does not contain rows for validation.'],
                'warnings' => [],
                'config' => null,
            ];
        }

        $requiredError = $this->validateRequiredColumns($record, $columnKeys);
        if ($requiredError !== null) {
            return [
                'ok' => false,
                'errors' => [$requiredError],
                'warnings' => [],
                'config' => null,
            ];
        }

        $validators = $this->validatorsByKey($record);
        $errors = [];
        $rowErrors = [];
        $warnings = [];
        $maxErrors = 20;
        $maxRowErrors = 20;
        $maxWarnings = 20;
        $skippedDuplicateRows = 0;
        $rowIndex = $hasManualConfig
            ? ((int) ($configured['skip_first_row'] ?? 0) === 1 ? 1 : 0)
            : 1;
        foreach ($dataLines as $line) {
            $rowIndex++;
            $rawValues = str_getcsv($line, $separator);

            if (count($rawValues) !== count($columnKeys)) {
                $errors[] = "Line {$rowIndex}: number of values does not match header column count.";
                if (count($errors) >= $maxErrors) {
                    break;
                }

                continue;
            }

            $row = [];
            foreach ($rawValues as $index => $value) {
                $columnKey = $columnKeys[$index] ?? self::IGNORE_COLUMN;
                if (! is_string($columnKey) || $columnKey === self::IGNORE_COLUMN) {
                    continue;
                }

                $row[$columnKey] = is_string($value) ? trim($value) : $value;
            }

            $rules = [];
            foreach (array_keys($row) as $columnKey) {
                if (isset($validators[$columnKey])) {
                    $rules[$columnKey] = $validators[$columnKey];
                }
            }

            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Line {$rowIndex}: {$message}";
                    if (count($errors) >= $maxErrors) {
                        break 2;
                    }
                }

                continue;
            }

            $duplicate = $this->duplicateForRow($record, $row);
            if ($duplicate === null) {
                continue;
            }

            $skippedDuplicateRows++;

            if ($duplicate['status'] === 'same') {
                if (count($warnings) < $maxWarnings) {
                    $warnings[] = $this->duplicateWarningMessage($rowIndex, $duplicate['existing_id']);
                }

                continue;
            }

            if (count($rowErrors) < $maxRowErrors) {
                $rowErrors[] = $this->duplicateConflictMessage($rowIndex, $duplicate['existing_id'], $duplicate['differences']);
            }
        }

        if ($skippedDuplicateRows === count($dataLines)) {
            $errors[] = 'All rows would be skipped because they already exist in the database. There is nothing to import.';
            $errors = array_values(array_unique(array_merge($errors, $rowErrors, $warnings)));
        }

        if (count($errors) > 0) {
            return [
                'ok' => false,
                'errors' => $errors,
                'row_errors' => $rowErrors,
                'warnings' => $warnings,
                'config' => null,
            ];
        }

        return [
            'ok' => true,
            'errors' => [],
            'row_errors' => $rowErrors,
            'warnings' => $warnings,
            'config' => UploadQueueConfig::configured(
                $separator,
                $hasManualConfig ? ((int) ($configured['skip_first_row'] ?? 0) === 1 ? 1 : 0) : 1,
                $columnKeys,
            )
                ->withDetailedValidation(
                    true,
                    count($dataLines),
                    now()->toISOString(),
                )
                ->toArray(),
        ];
    }

    private function normalizeSeparator(string $separator): string
    {
        if ($separator === '\\t' || mb_strtolower($separator) === 'tab') {
            return "\t";
        }

        return in_array($separator, [',', ';', "\t"], true) ? $separator : ',';
    }

    private function detectSeparator(string $headerLine): string
    {
        $candidates = [',', ';', "\t"];
        $bestSeparator = ',';
        $bestColumns = 1;

        foreach ($candidates as $candidate) {
            $columns = count(str_getcsv($headerLine, $candidate));
            if ($columns > $bestColumns) {
                $bestColumns = $columns;
                $bestSeparator = $candidate;
            }
        }

        return $bestSeparator;
    }

    private function buildColumnMapping(UploadQueue $record, array $headers): array
    {
        $options = $this->validatorClasses($record);
        $lookup = [];
        foreach ($options as $className) {
            if (! property_exists($className, 'key')) {
                continue;
            }

            $key = $className::$key;
            $lookup[$this->normalizeKey($key)] = $key;
            if (property_exists($className, 'label')) {
                $lookup[$this->normalizeKey($className::$label)] = $key;
            }
        }

        return array_map(function ($header) use ($lookup) {
            $normalized = $this->normalizeKey((string) $header);

            return $lookup[$normalized] ?? self::IGNORE_COLUMN;
        }, $headers);
    }

    private function normalizeKey(string $value): string
    {
        return mb_strtolower(
            preg_replace('/[^a-z0-9]+/i', '', trim($value)) ?? ''
        );
    }

    private function validateRequiredColumns(UploadQueue $record, array $columnKeys): ?string
    {
        if (! in_array(ColumnSmiles::$key, $columnKeys, true)) {
            return 'Column '.ColumnSmiles::$label.' is required.';
        }

        if ($record->type === UploadQueue::TYPE_ACTIVE_DATASET) {
            if (! in_array(ColumnTarget::$key, $columnKeys, true)) {
                return 'Column '.ColumnTarget::$label.' is required for active interactions.';
            }

            if (
                ! in_array(ColumnEc50::$key, $columnKeys, true) &&
                ! in_array(ColumnIc50::$key, $columnKeys, true) &&
                ! in_array(ColumnKi::$key, $columnKeys, true) &&
                ! in_array(ColumnKm::$key, $columnKeys, true)
            ) {
                return 'At least one active interaction value column is required (EC50, IC50, Ki or Km).';
            }
        }

        if ($record->type === UploadQueue::TYPE_PASSIVE_DATASET) {
            if (
                ! in_array(ColumnGpen::$key, $columnKeys, true) &&
                ! in_array(ColumnGwat::$key, $columnKeys, true) &&
                ! in_array(ColumnLogK::$key, $columnKeys, true) &&
                ! in_array(ColumnLogPerm::$key, $columnKeys, true) &&
                ! in_array(ColumnXmin::$key, $columnKeys, true)
            ) {
                return 'At least one passive interaction value column is required (Gpen, Gwat, LogK, LogPerm or Xmin).';
            }
        }

        return null;
    }

    private function validatorsByKey(UploadQueue $record): array
    {
        $validators = [];
        foreach ($this->validatorClasses($record) as $className) {
            if (! property_exists($className, 'key')) {
                continue;
            }
            $validators[$className::$key] = new $className;
        }

        return $validators;
    }

    private function validatorClasses(UploadQueue $record): array
    {
        return $this->columns->validatorClasses($record);
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: 'same'|'conflict', existing_id: int, differences: array<string, array{database: mixed, upload: mixed}>}|null
     */
    private function duplicateForRow(UploadQueue $record, array $row): ?array
    {
        $payload = $record->type === UploadQueue::TYPE_ACTIVE_DATASET
            ? $this->payloadBuilder->activePayload($record, $row, false)
            : $this->payloadBuilder->passivePayload($record, $row, false);

        return $this->duplicateChecker->check($record, $payload, $this->interactionValueColumns($record));
    }

    /**
     * @return array<int, string>
     */
    private function interactionValueColumns(UploadQueue $record): array
    {
        return array_values($this->columns->interactionValueColumns($record));
    }

    private function duplicateWarningMessage(int $rowIndex, int $existingId): string
    {
        return "Line {$rowIndex}: interaction already exists in database as ID {$existingId} with the same values. Row will be skipped during import.";
    }

    /**
     * @param  array<string, array{database: mixed, upload: mixed}>  $differences
     */
    private function duplicateConflictMessage(int $rowIndex, int $existingId, array $differences): string
    {
        $values = collect($differences)
            ->map(fn (array $difference, string $column): string => "{$column}: DB={$difference['database']}, upload={$difference['upload']}")
            ->implode('; ');

        return "Line {$rowIndex}: interaction already exists in database as ID {$existingId}, but has different values after rounding to 2 decimals ({$values}). Row will be skipped.";
    }
}
