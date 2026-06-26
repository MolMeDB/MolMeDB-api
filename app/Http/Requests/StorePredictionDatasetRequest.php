<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\PredictionWorkers\Models\Prediction;

class StorePredictionDatasetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'membranes' => ['required', 'array', 'min:1', 'max:10'],
            'membranes.*' => ['integer', 'distinct'],
            'methods' => ['required', 'array', 'min:1', 'max:5'],
            'methods.*' => ['string', 'distinct', Rule::in(array_keys(Prediction::remotePredictionMethodOptions()))],
            'smiles' => ['required', 'array', 'min:1', 'max:100'],
            'smiles.*' => ['string', 'max:4000'],
            'temperature' => ['required', 'numeric', 'between:20,45'],
            'priority' => ['required'],
            'description' => ['required', 'string', 'max:512'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'membranes' => 'membranes',
            'methods' => 'methods',
            'smiles' => 'SMILES',
            'description' => 'description',
        ];
    }
}
