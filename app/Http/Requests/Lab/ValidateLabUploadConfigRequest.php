<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateLabUploadConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'separator' => ['required', 'string', Rule::in([',', ';', "\t", '\\t', 'tab'])],
            'skip_first_row' => ['required', 'integer', Rule::in([0, 1])],
            'attributes' => ['required', 'array', 'min:1'],
            'attributes.*' => ['nullable', 'string', 'max:64'],
        ];
    }
}
