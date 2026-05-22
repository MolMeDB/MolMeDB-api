<?php
namespace App\Rules\UploadFile\Identifiers;

use App\Rules\UploadFile\ColumnTypeInterface;
use App\Services\External\Chemical\Unichem\Unichem;
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
        $this->validate_fast($attribute, $value, $fail);
    }

    public function validate_fast(string $attribute, mixed $value, Closure $fail): void
    {
        $maxLength = self::$maxLength;
        $value = trim($value);
        if (! is_string($value) || empty($value) || strlen($value) > $maxLength || ! preg_match('/^([A-N,R-Z][0-9]([A-Z][A-Z, 0-9][A-Z, 0-9][0-9]){1,2})|([O,P,Q][0-9][A-Z, 0-9][A-Z, 0-9][A-Z, 0-9][0-9])(\.\d+)?$/', $value)) {
            $fail('Column '.self::$label." must be a valid UniProt ID.");
        }
    }
}