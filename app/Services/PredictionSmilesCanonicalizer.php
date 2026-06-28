<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PredictionSmilesCanonicalizer
{
    /**
     * @param  array<int, mixed>  $values
     * @return array{smiles: array<int, string>, duplicates_removed: int}
     *
     * @throws ValidationException
     */
    public function canonicalize(array $values): array
    {
        $entries = collect($values)
            ->map(fn (mixed $value, int $index): array => [
                'line' => $index + 1,
                'smiles' => trim((string) $value),
            ])
            ->filter(fn (array $entry): bool => $entry['smiles'] !== '' && ! str_starts_with($entry['smiles'], '#'))
            ->values();

        if ($entries->isEmpty()) {
            throw ValidationException::withMessages([
                'smiles' => ['At least one non-empty SMILES is required.'],
            ]);
        }

        $uniqueEntries = $entries->unique('smiles')->values();
        $duplicatesRemoved = $entries->count() - $uniqueEntries->count();
        $canonicalByInput = [];
        $uncachedEntries = [];

        foreach ($uniqueEntries as $entry) {
            $cacheKey = $this->cacheKey($entry['smiles']);
            $cached = Cache::get($cacheKey);

            if (is_string($cached) && $cached !== '') {
                $canonicalByInput[$entry['smiles']] = $cached;
            } else {
                $uncachedEntries[] = $entry;
            }
        }

        if ($uncachedEntries !== []) {
            $this->canonicalizeUncached($uncachedEntries, $canonicalByInput);
        }

        $canonical = $uniqueEntries
            ->map(fn (array $entry): ?string => $canonicalByInput[$entry['smiles']] ?? null)
            ->filter()
            ->values();
        $uniqueCanonical = $canonical->unique()->values();
        $duplicatesRemoved += $canonical->count() - $uniqueCanonical->count();

        return [
            'smiles' => $uniqueCanonical->all(),
            'duplicates_removed' => $duplicatesRemoved,
        ];
    }

    /**
     * @param  array<int, array{line: int, smiles: string}>  $entries
     * @param  array<string, string>  $canonicalByInput
     *
     * @throws ValidationException
     */
    private function canonicalizeUncached(array $entries, array &$canonicalByInput): void
    {
        $baseUrl = rtrim((string) config('services.rdkit.url'), '/');

        if ($baseUrl === '') {
            throw ValidationException::withMessages([
                'smiles' => ['SMILES validation service is not configured.'],
            ]);
        }

        $responses = Http::pool(
            fn (Pool $pool): array => collect($entries)
                ->map(fn (array $entry, int $index) => $pool
                    ->as((string) $index)
                    ->acceptJson()
                    ->connectTimeout(3)
                    ->timeout(30)
                    ->get($baseUrl.'/structure/canonize', ['smi' => $entry['smiles']]))
                ->all(),
            concurrency: 10,
        );
        $errors = [];

        foreach ($entries as $index => $entry) {
            $response = $responses[(string) $index] ?? null;
            $canonical = $response instanceof Response && $response->successful()
                ? $response->json('data')
                : null;

            if (! is_string($canonical) || trim($canonical) === '') {
                $errors[] = "SMILES on line {$entry['line']} is invalid.";

                continue;
            }

            $canonical = trim($canonical);
            $canonicalByInput[$entry['smiles']] = $canonical;
            Cache::put($this->cacheKey($entry['smiles']), $canonical, now()->addDay());
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['smiles' => $errors]);
        }
    }

    private function cacheKey(string $smiles): string
    {
        return 'prediction:canonical-smiles:'.hash('sha256', $smiles);
    }
}
