<?php
namespace App\Rules\UploadFile\Identifiers;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;

class ColumnName implements ColumnTypeInterface
{
    public static string $key = 'name';
    public static string $label = 'Name';

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
    }
}