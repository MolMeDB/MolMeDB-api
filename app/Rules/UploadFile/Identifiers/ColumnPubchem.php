<?php
namespace App\Rules\UploadFile\Identifiers;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;

class ColumnPubchem implements ColumnTypeInterface
{
    public static string $key = 'pubchem';
    public static string $label = 'Pubchem ID';

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
        if(!preg_match('/^\d+$/', $value)) {
            $fail("Column " . self::$label . " must be a valid PubChem ID. Valid format: numeric value.");
        }
    }
}