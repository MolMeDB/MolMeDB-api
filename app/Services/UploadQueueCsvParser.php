<?php

namespace App\Services;

class UploadQueueCsvParser
{
    public function normalizeSeparator(string $separator): string
    {
        if ($separator === '\\t' || mb_strtolower($separator) === 'tab') {
            return "\t";
        }

        return in_array($separator, [',', ';', "\t"], true) ? $separator : ',';
    }

    /**
     * @return array<int, string|null>
     */
    public function parseLine(string $line, string $separator): array
    {
        $separator = $this->normalizeSeparator($separator);
        $line = rtrim(mb_convert_encoding($line, 'UTF-8', 'auto'), "\r\n");
        $values = str_getcsv($line, $separator, '"', '\\');

        if ($this->isWrappedCsvLine($line, $values, $separator)) {
            $innerValues = str_getcsv((string) $values[0], $separator, '"', '\\');

            if (count($innerValues) > 1) {
                return $innerValues;
            }
        }

        return $values;
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function isWrappedCsvLine(string $line, array $values, string $separator): bool
    {
        if (count($values) !== 1 || ! is_string($values[0])) {
            return false;
        }

        return str_starts_with($line, '"')
            && str_ends_with($line, '"')
            && str_contains($values[0], $separator);
    }
}
