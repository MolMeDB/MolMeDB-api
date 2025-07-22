<?php
namespace App\Rules\UploadFile\Identifiers;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;

class ColumnSmiles implements ColumnTypeInterface
{
    public static string $key = 'smiles';
    public static string $label = 'SMILES';

    public static int $maxLength = 4000;

    public static function make(): static
    {
        return new static();
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $maxLength = self::$maxLength;
        if (!is_string($value) || empty($value) || strlen($value) > $maxLength || strlen($value) < 1) {
            $fail("Column " . self::$label . " must be a string between 1 and $maxLength characters.");
        }

        // $fail('Column ' . self::$label . ' is not supported yet.');
    }
}