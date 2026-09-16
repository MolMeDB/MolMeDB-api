<?php

namespace App\Rules\UploadFile\Identifiers;

use App\Rules\UploadFile\ColumnTypeInterface;
use App\Services\External\Chemical\Unichem\Unichem;
use App\Services\UploadQueueExternalLookupCache;
use Closure;

class ColumnPdb implements ColumnTypeInterface
{
    public static string $key = 'pdb';

    public static string $label = 'RCSB ligand ID';

    public static int $maxLength = 255;

    public static function make(): static
    {
        return new static;
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        $this->validate_fast($attribute, $value, $fail);

        if (! $this->exists_remotely($value)) {
            $fail('Column '.self::$label." contains unknown ID '{$value}'.");
        }
    }

    public function validate_fast(string $attribute, mixed $value, Closure $fail): void
    {
        $maxLength = self::$maxLength;
        $value = trim($value);
        if (! is_string($value) || empty($value) || strlen($value) > $maxLength || ! preg_match('/^\w+$/', $value)) {
            $fail('Column '.self::$label." must be a string with a maximum length of $maxLength characters.");
        }
    }

    public function exists_remotely(string $value): bool
    {
        return app(UploadQueueExternalLookupCache::class)->unichemIdentifierExists(Unichem::SOURCE_RCSB_PDB, $value);
    }
}
