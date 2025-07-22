<?php
namespace App\Rules\UploadFile\PassiveInteractions;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;
use Illuminate\Support\Facades\Validator;

class ColumnGwat implements ColumnTypeInterface
{
    public static string $key = 'g_wat';
    public static string $label = 'Gwat';

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