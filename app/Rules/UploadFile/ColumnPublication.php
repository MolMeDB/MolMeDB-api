<?php
namespace App\Rules\UploadFile;

use Closure;

class ColumnPublication implements ColumnTypeInterface
{
    public static string $key = 'publication';
    public static string $label = 'Publication';

    public static function make(): static
    {
        return new static();
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $fail('Column ' . self::$label . ' is not supported yet.');
    }
}