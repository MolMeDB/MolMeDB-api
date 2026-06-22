<?php

namespace App\Http\Requests\Downloader;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DownloaderSelectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'membrane_ids' => ['sometimes', 'array'],
            'membrane_ids.*' => ['integer', 'distinct', 'exists:membranes,id'],
            'method_ids' => ['sometimes', 'array'],
            'method_ids.*' => ['integer', 'distinct', 'exists:methods,id'],
            'protein_ids' => ['sometimes', 'array'],
            'protein_ids.*' => ['integer', 'distinct', 'exists:proteins,id'],
            'structure_identifiers' => ['sometimes', 'array'],
            'structure_identifiers.*' => ['string', 'distinct', 'exists:structures,identifier'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasSelection = collect([
                    $this->input('membrane_ids', []),
                    $this->input('method_ids', []),
                    $this->input('protein_ids', []),
                    $this->input('structure_identifiers', []),
                ])->contains(fn (mixed $values): bool => is_array($values) && $values !== []);

                if (! $hasSelection) {
                    $validator->errors()->add('selection', 'Select at least one downloader item.');
                }
            },
        ];
    }

    public function membraneIds(): array
    {
        return $this->validated('membrane_ids', []);
    }

    public function methodIds(): array
    {
        return $this->validated('method_ids', []);
    }

    public function structureIdentifiers(): array
    {
        return $this->validated('structure_identifiers', []);
    }

    public function proteinIds(): array
    {
        return $this->validated('protein_ids', []);
    }
}
