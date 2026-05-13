<?php

namespace App\Rules\UploadFile;

use Closure;
use Illuminate\Support\Facades\Validator;

class ColumnCharge implements ColumnTypeInterface
{
    public static string $key = 'charge';

    public static string $label = 'Charge [Q]';

    public static function make(): static
    {
        return new static;
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $validator = Validator::make(
            [$attribute => $value],
            [$attribute => 'integer|min:-20|max:20']
        );

        if ($validator->fails()) {
            $fail('Column '.self::$label.' must be an integer between -20 and 20.');
        }
    }
}
