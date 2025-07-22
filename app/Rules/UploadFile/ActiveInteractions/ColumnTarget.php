<?php
namespace App\Rules\UploadFile\ActiveInteractions;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;

class ColumnTarget implements ColumnTypeInterface
{
    public static string $key = 'active_target';
    public static string $label = 'Target';

    public static int $maxLength = 255;

    public static function make(): static
    {
        return new static();
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {  
        $maxLength = self::$maxLength;
        if(!$value || strlen($value) <= 2 || strlen($value) > $maxLength) {
            $fail("Column " . self::$label . " must be a string between 3 and $maxLength characters.");
        }
    }
}