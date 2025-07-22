<?php
namespace App\Rules\UploadFile;

use Closure;
use Illuminate\Support\Facades\Validator;

class ColumnPh implements ColumnTypeInterface
{
    public static string $key = 'ph';
    public static string $label = 'pH';

    public static function make(): static
    {
        return new static();
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        // use native numeric validator
        $validator = Validator::make(
            [$attribute => $value],
            [$attribute => 'numeric;min:0;max:14']
        );

        if ($validator->fails()) {
            $fail("Column " . self::$label . " must be a number between 0 and 14.");
        }
    }
}