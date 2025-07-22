<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use App\Models\File;

class FileUniqueByHash implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!($value instanceof UploadedFile)) {
            $fail('Invalid file type. Try again.');
            return;
        }

        try {
            $hash = md5_file($value->getRealPath());
        } catch (\Exception $e) {
            $fail('Cannot read the file. Try again.');
            return;
        }

        // Kontrola existence v databázi
        if (File::where('hash', $hash)->exists()) {
            $fail('This file is already present in the database.');
        }
    }
}