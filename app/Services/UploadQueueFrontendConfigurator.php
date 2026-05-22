<?php

namespace App\Services;

use App\Models\UploadQueue;
use App\Rules\UploadFile\ActiveInteractions\ColumnEc50;
use App\Rules\UploadFile\ActiveInteractions\ColumnIc50;
use App\Rules\UploadFile\ActiveInteractions\ColumnKi;
use App\Rules\UploadFile\ActiveInteractions\ColumnKm;
use App\Rules\UploadFile\ActiveInteractions\ColumnTarget;
use App\Rules\UploadFile\FastColumnTypeRule;
use App\Rules\UploadFile\Identifiers\ColumnSmiles;
use App\Rules\UploadFile\PassiveInteractions\ColumnGpen;
use App\Rules\UploadFile\PassiveInteractions\ColumnGwat;
use App\Rules\UploadFile\PassiveInteractions\ColumnLogK;
use App\Rules\UploadFile\PassiveInteractions\ColumnLogPerm;
use App\Rules\UploadFile\PassiveInteractions\ColumnXmin;
use App\ValueObjects\UploadQueueConfig;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UploadQueueFrontendConfigurator
{
    public const IGNORE_COLUMN = 'ignore';

    /**
     * @return array<string, string>
     */
    public function columnTypeOptions(UploadQueue $record): array
    {
        $classes = $this->validatorClasses($record);
        $result = [self::IGNORE_COLUMN => 'Ignore'];

        foreach ($classes as $className) {
            $result[$className::$key] = $className::$label;
        }

        return $result;
    }

    /**
     * @return array{ok: bool, errors: array<int, string>, preview_rows: array<int, array<int, string>>, total_rows: int, start_line: int, column_mapping: array<int, string>, column_type_options: array<string, string>, column_valid_types: array<int, array<string, string>>}
     */
    public function preview(
        UploadQueue $record,
        string $separator,
        int $skipFirstRow = 1,
        int $startLine = 1,
        int $limit = 5,
    ): array {
        $separator = $this->normalizeSeparator($separator);
        $skipFirstRow = $skipFirstRow === 1 ? 1 : 0;
        $startLine = max(1, $startLine);
        $limit = max(1, min(10, $limit));

        $stream = $this->openStream($record);
        if (! $stream) {
            return [
                'ok' => false,
                'errors' => ['Cannot read uploaded file.'],
                'preview_rows' => [],
                'total_rows' => 0,
                'start_line' => $startLine,
                'column_mapping' => [],
                'column_type_options' => $this->columnTypeOptions($record),
                'column_valid_types' => [],
            ];
        }

        $previewRows = [];
        $lineNumber = 0;
        while (($line = fgets($stream)) !== false) {
            $lineNumber++;

            if ($skipFirstRow === 1 && $lineNumber === 1) {
                continue;
            }

            if ($lineNumber < $startLine) {
                continue;
            }

            $previewRows[] = str_getcsv(mb_convert_encoding($line, 'UTF-8', 'auto'), $separator, '"', '\\');
            if (count($previewRows) >= $limit) {
                break;
            }
        }

        $totalRows = $lineNumber;
        while (fgets($stream) !== false) {
            $totalRows++;
        }

        fclose($stream);

        $columnCount = count($previewRows[0] ?? []);
        $columnMapping = $this->defaultColumnMapping($record, $columnCount);
        $columnTypeOptions = $this->columnTypeOptions($record);
        $columnValidTypes = $this->columnValidTypesForMapping($columnMapping, $columnTypeOptions);

        return [
            'ok' => true,
            'errors' => [],
            'preview_rows' => $previewRows,
            'total_rows' => $totalRows,
            'start_line' => $startLine,
            'column_mapping' => $columnMapping,
            'column_type_options' => $columnTypeOptions,
            'column_valid_types' => $columnValidTypes,
        ];
    }

    /**
     * @param  array<int, string|null>  $columnMapping
     * @return array{ok: bool, errors: array<int, string>, warnings: array<int, string>, config: array<string, mixed>|null}
     */
    public function validateConfiguration(
        UploadQueue $record,
        string $separator,
        int $skipFirstRow,
        array $columnMapping,
    ): array {
        $separator = $this->normalizeSeparator($separator);
        $skipFirstRow = $skipFirstRow === 1 ? 1 : 0;
        $columnMapping = array_map(function ($value) {
            if (! is_string($value) || trim($value) === '') {
                return self::IGNORE_COLUMN;
            }

            return trim($value);
        }, array_values($columnMapping));

        $requiredError = $this->validateRequiredColumns($record, $columnMapping);
        if ($requiredError !== null) {
            return [
                'ok' => false,
                'errors' => [$requiredError],
                'warnings' => [],
                'config' => null,
            ];
        }

        $stream = $this->openStream($record);
        if (! $stream) {
            return [
                'ok' => false,
                'errors' => ['Cannot read uploaded file.'],
                'warnings' => [],
                'config' => null,
            ];
        }

        $validators = $this->validatorsByKey($record);
        $errors = [];
        $warnings = [];
        $maxErrors = 20;
        $lineNumber = 0;
        $validatedRows = 0;

        while (($line = fgets($stream)) !== false) {
            $lineNumber++;

            if ($skipFirstRow === 1 && $lineNumber === 1) {
                continue;
            }

            $line = mb_convert_encoding($line, 'UTF-8', 'auto');
            $rawValues = str_getcsv($line, $separator, '"', '\\');

            if (count($rawValues) !== count($columnMapping)) {
                $errors[] = "Line {$lineNumber}: number of values does not match configured column count.";
                if (count($errors) >= $maxErrors) {
                    break;
                }

                continue;
            }

            $row = [];
            foreach ($rawValues as $index => $value) {
                $columnKey = $columnMapping[$index] ?? self::IGNORE_COLUMN;
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
                    $errors[] = "Line {$lineNumber}: {$message}";
                    if (count($errors) >= $maxErrors) {
                        break 2;
                    }
                }
            }

            foreach ($this->validatorClasses($record) as $validatorClass) {
                if (! isset($row[$validatorClass::$key]) || isset($warnings[$validatorClass::$key])) {
                    continue;
                }

                if (! method_exists($validatorClass, 'isOutOfLimits')) {
                    continue;
                }

                try {
                    if ((new $validatorClass)->isOutOfLimits($row[$validatorClass::$key], $record->dataset?->method)) {
                        $warnings[$validatorClass::$key] = 'Some values for column '.$validatorClass::$label.' are out of method limits.';
                    }
                } catch (\Throwable) {
                    // Warnings are best-effort only.
                }
            }

            $validatedRows++;
        }

        fclose($stream);

        if (count($errors) > 0) {
            return [
                'ok' => false,
                'errors' => $errors,
                'warnings' => array_values($warnings),
                'config' => null,
            ];
        }

        return [
            'ok' => true,
            'errors' => [],
            'warnings' => array_values($warnings),
            'config' => UploadQueueConfig::configured($separator, $skipFirstRow, $columnMapping)
                ->withQuickValidation(
                    true,
                    $validatedRows,
                    now()->toISOString(),
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

    /**
     * @return resource|false
     */
    private function openStream(UploadQueue $record)
    {
        $disk = $record->file?->storage;
        $path = $record->file?->path;

        if (! is_string($disk) || trim($disk) === '') {
            return false;
        }

        if (! is_string($path) || $path === '' || ! Storage::disk($disk)->exists($path)) {
            return false;
        }

        return Storage::disk($disk)->readStream($path);
    }

    /**
     * @return array<int, string>
     */
    private function defaultColumnMapping(UploadQueue $record, int $columnCount): array
    {
        $configured = $record->config->attributes();
        if (count($configured) === $columnCount) {
            return array_map(function ($value) {
                return is_string($value) && trim($value) !== '' ? trim($value) : self::IGNORE_COLUMN;
            }, $configured);
        }

        return array_fill(0, $columnCount, self::IGNORE_COLUMN);
    }

    /**
     * @param  array<int, string>  $mapping
     * @param  array<string, string>  $availableTypes
     * @return array<int, array<string, string>>
     */
    private function columnValidTypesForMapping(array $mapping, array $availableTypes): array
    {
        return array_map(function ($selectedValue, $index) use ($mapping, $availableTypes) {
            return array_filter($availableTypes, function ($label, $value) use ($mapping, $selectedValue) {
                return $value === self::IGNORE_COLUMN || ! in_array($value, $mapping, true) || $value === $selectedValue;
            }, ARRAY_FILTER_USE_BOTH);
        }, $mapping, array_keys($mapping));
    }

    /**
     * @param  array<int, string>  $columnKeys
     */
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

    /**
     * @return array<string, object>
     */
    private function validatorsByKey(UploadQueue $record): array
    {
        $validators = [];
        foreach ($this->validatorClasses($record) as $className) {
            $validators[$className::$key] = new FastColumnTypeRule(new $className);
        }

        return $validators;
    }

    /**
     * @return array<int, class-string>
     */
    private function validatorClasses(UploadQueue $record): array
    {
        return app(UploadQueueColumnRegistry::class)->validatorClasses($record);
    }
}
