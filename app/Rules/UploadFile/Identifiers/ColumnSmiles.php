<?php

namespace App\Rules\UploadFile\Identifiers;

use App\Rules\UploadFile\ColumnTypeInterface;
use Closure;
use Modules\Rdkit\Rdkit;

class ColumnSmiles implements ColumnTypeInterface
{
    public static string $key = 'smiles';

    public static string $label = 'SMILES';

    public static int $maxLength = 4000;

    public static function make(): static
    {
        return new static;
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $maxLength = self::$maxLength;
        if (! is_string($value) || empty($value) || strlen($value) > $maxLength || strlen($value) < 1) {
            $fail('Column '.self::$label." must be a string between 1 and $maxLength characters.");
        }

        $rdkit = new Rdkit;

        if (! $rdkit->is_connected()) {
            $fail('RDKit service is not available for validating '.self::$label.' column.');
        }

        if (! $rdkit->canonize_smiles($value)) {
            $fail('Column '.self::$label.' contains invalid SMILES string.');
        }
    }

    public function validate_fast(string $attribute, mixed $value, Closure $fail): void
    {
        $this->validate($attribute, $value, $fail);
    }
}
