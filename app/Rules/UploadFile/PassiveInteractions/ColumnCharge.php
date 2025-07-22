<?php
namespace App\Rules\UploadFile\PassiveInteractions;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;

class ColumnCharge implements ColumnTypeInterface
{
    public static string $key = 'charge';
    public static string $label = 'Charge';

    public static function make(): static
    {
        return new static();
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $fail('Column ' . self::$label . ' is not supported yet. The value is comuted from the SMILES representation.');
    }
}