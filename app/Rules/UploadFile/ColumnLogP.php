<?php
namespace App\Rules\UploadFile;

use Closure;
use Illuminate\Support\Facades\Validator;

class ColumnLogP implements ColumnTypeInterface
{
    public static string $key = 'logp';
    public static string $label = 'LogP';

    public static function make(): static
    {
        return new static();
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        // use native numeric validator
        $validator = Validator::make(
            [$attribute => $value],
            [$attribute => 'numeric']
        );

        if ($validator->fails()) {
            $fail("Column " . self::$label . " must be a number.");
        }
    }
}