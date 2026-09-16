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
            [$attribute => 'numeric|min:0']
        );

        if ($validator->fails()) {
            $fail('Column '.self::$label.' must be a number greater than or equal to 0 [°C].');
        }
    }

    public function validate_fast(string $attribute, mixed $value, Closure $fail): void
    {
        $this->validate($attribute, $value, $fail);
    }
}
