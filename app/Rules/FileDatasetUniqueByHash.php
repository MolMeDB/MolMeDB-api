<?php

namespace App\Rules;

use App\Models\File;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

class FileDatasetUniqueByHash implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ($value instanceof UploadedFile)) {
            $fail('Invalid file type. Try again.');

            return;
        }

        try {
            $hash = md5_file($value->getRealPath());
        } catch (Exception $e) {
            $fail('Cannot read the file. Try again.');

            return;
        }

        $file = File::where('hash', $hash)
            ->whereHas('upload_queue');

        if ($file->exists()) {
            $fail('This file was already uploaded as part of another dataset. Please check your file and try again.');
        }
    }
}
