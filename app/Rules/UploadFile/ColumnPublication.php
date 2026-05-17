<?php

namespace App\Rules\UploadFile;

use App\Services\PublicationReferenceResolver;
use Closure;

class ColumnPublication implements ColumnTypeInterface
{
    public static string $key = 'publication';

    public static string $label = 'Publication';

    public static function make(): static
    {
        return new static;
    }

    public function validate(string $attribute, $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Column '.static::$label.' must contain a DOI or PubMed ID.');

            return;
        }

        $resolver = app(PublicationReferenceResolver::class);
        $reference = $resolver->normalizeReference($value);

        if ($reference === '') {
            $fail('Column '.static::$label.' must contain a DOI or PubMed ID.');

            return;
        }

        if (! $resolver->referenceExists($reference)) {
            $fail('Column '.static::$label." contains unknown publication reference '{$reference}'. Use a valid DOI or PubMed ID.");
        }
    }

    public function validate_fast(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Column '.static::$label.' must contain a DOI or PubMed ID.');

            return;
        }

        $resolver = app(PublicationReferenceResolver::class);
        $reference = $resolver->normalizeReference($value);

        if ($reference === '') {
            $fail('Column '.static::$label.' must contain a DOI or PubMed ID.');

            return;
        }
    }
}
