<?php

namespace App\Rules\UploadFile\Identifiers;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;

class ColumnDrugbank implements ColumnTypeInterface
{
    public static string $key = 'drugbank';

    public static string $label = 'Drugbank ID';

    public static function make(): static
    {
        return new static;
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $this->validate_fast($attribute, $value, $fail);
    }

    public function validate_fast(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || empty($value) || ! preg_match('/^(DB\d{5}|BE\d{7})$/', $value)) {
            $fail('Column '.self::$label.' must be a string in the format DBXXXXX or BEXXXXXXX.');
        }
    }
}
