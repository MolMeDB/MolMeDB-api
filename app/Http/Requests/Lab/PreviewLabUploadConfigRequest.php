<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewLabUploadConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'separator' => ['nullable', 'string', Rule::in([',', ';', "\t", '\\t', 'tab'])],
            'skip_first_row' => ['nullable', 'integer', Rule::in([0, 1])],
            'start_line' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
