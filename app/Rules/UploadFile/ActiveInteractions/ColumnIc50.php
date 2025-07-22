<?php
namespace App\Rules\UploadFile\ActiveInteractions;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;
use Illuminate\Support\Facades\Validator;

class ColumnIc50 implements ColumnTypeInterface
{
    public static string $key = 'Ic50';
    public static string $label = 'Ic50';

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