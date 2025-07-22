<?php
namespace App\Rules\UploadFile\PassiveInteractions;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;
use Illuminate\Support\Facades\Validator;

class ColumnLogK implements ColumnTypeInterface
{
    public static string $key = 'logk';
    public static string $label = 'LogK';

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