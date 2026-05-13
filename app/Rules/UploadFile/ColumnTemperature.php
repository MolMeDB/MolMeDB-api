<?php

namespace App\Rules\UploadFile;

use Closure;
use Illuminate\Support\Facades\Validator;

class ColumnTemperature implements ColumnTypeInterface
{
    public static string $key = 'temperature';

    public static string $label = 'Temperature [C]';

    public static function make(): static
    {
        return new static;
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $validator = Validator::make(
            [$attribute => $value],
            [$attribute => 'numeric|min:-273.15']
        );

        if ($validator->fails()) {
            $fail('Column '.self::$label.' must be a number greater than or equal to -273.15.');
        }
    }
}
