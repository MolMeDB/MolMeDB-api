<?php

namespace App\Rules\UploadFile;

use Closure;

class ColumnComment implements ColumnTypeInterface
{
    public static string $key = 'comment';

    public static string $label = 'Comment';

    public static string $databaseColumn = 'note';

    public static int $maxLength = 255;

    public static function make(): static
    {
        return new static;
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $ml = self::$maxLength;
        if (strlen($value) > $ml) {
            $fail('Column '.self::$label." must be a string with a maximum of $ml characters.");
        }
    }

    public function validate_fast(string $attribute, mixed $value, Closure $fail): void
    {
        $this->validate($attribute, $value, $fail);
    }
}
