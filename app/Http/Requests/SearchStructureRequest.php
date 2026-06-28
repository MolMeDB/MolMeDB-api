<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class SearchStructureRequest extends FormRequest
{
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
            'query' => ['nullable', 'string', 'max:4000'],
            'smiles' => ['nullable', 'string', 'max:4000'],
            'substructure' => ['nullable', 'string', 'max:4000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $smiles = trim((string) $this->input('substructure'));

                if (
                    $smiles === ''
                    || $validator->errors()->has('substructure')
                    || DB::getDriverName() !== 'pgsql'
                ) {
                    return;
                }

                $error = DB::scalar('SELECT bingo.checkMolecule(?)', [$smiles]);

                if (filled($error)) {
                    $validator->errors()->add(
                        'substructure',
                        'The substructure SMILES is invalid.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return Arr::except($this->validated(), ['per_page']);
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 10);
    }
}
