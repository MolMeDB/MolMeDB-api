<?php
namespace App\Rules\UploadFile\Identifiers;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;

class ColumnUniprot implements ColumnTypeInterface
{
    public static string $key = 'uniprot';
    public static string $label = 'Uniprot ID';

    public static int $maxLength = 255;

    public static function make(): static
    {
        return new static();
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $maxLength = self::$maxLength;
        if (!is_string($value) || empty($value) || strlen($value) > $maxLength || strlen($value) <= 2) {
            $fail("Column " . self::$label . " must be a string between 3 and $maxLength characters.");
        }

        $value = trim($value);
        if(!preg_match('/^([A-N,R-Z][0-9]([A-Z][A-Z, 0-9][A-Z, 0-9][0-9]){1,2})|([O,P,Q][0-9][A-Z, 0-9][A-Z, 0-9][A-Z, 0-9][0-9])(\.\d+)?$/', $value))
        {
            $fail("Column " . self::$label . " must be a valid UniProt ID. Check https://registry.identifiers.org/registry/uniprot .");
        }
    }
}