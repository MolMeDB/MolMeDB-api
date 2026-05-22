<?php

namespace App\Http\Requests\Lab;

use App\Rules\FileUniqueByHash;
use Illuminate\Foundation\Http\FormRequest;

class ReuploadLabUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:csv,txt,tsv,xls,xlsx,json',
                new FileUniqueByHash,
            ],
        ];
    }
}
