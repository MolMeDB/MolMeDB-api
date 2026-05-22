<?php

namespace App\Http\Requests\Lab;

use App\Models\Dataset;
use App\Rules\FileDatasetUniqueByHash;
use App\Rules\TurnstileToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLabUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'dataset_type' => ['required', 'integer', Rule::in([
                Dataset::TYPE_PASSIVE,
                Dataset::TYPE_ACTIVE,
            ])],
            'dataset_name' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'method_id' => ['required_if:dataset_type,'.Dataset::TYPE_PASSIVE, 'nullable', 'integer', 'exists:methods,id'],
            'membrane_id' => ['required_if:dataset_type,'.Dataset::TYPE_PASSIVE, 'nullable', 'integer', 'exists:membranes,id'],
            'publication_pmid' => ['required', 'string', 'max:50', 'regex:/^\d+$/'],
            'publication_lookup_provider' => ['nullable', 'string', Rule::in(['europe_pmc'])],
            'publication_lookup_source' => ['nullable', 'string', Rule::in(['MED'])],
            'turnstile_token' => ['required', 'string', new TurnstileToken($this->ip())],
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:csv,txt,tsv',
                // 'mimes:csv,txt,tsv,xls,xlsx,json',
                new FileDatasetUniqueByHash,
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('publication_pmid')) {
                    $validator->errors()->add('publication_pmid', 'Select publication from Europe PMC list.');
                }
            },
        ];
    }
}
