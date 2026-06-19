<?php

namespace App\Http\Requests\Feedback;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
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
            'email' => ['sometimes', 'required', 'email:rfc', 'max:255'],
            'verification_id' => ['sometimes', 'required', 'integer', 'exists:feedback_email_verifications,id'],
            'verification_token' => ['sometimes', 'required', 'string', 'max:255'],
            'context' => ['required', 'string', 'max:2048'],
            'message' => ['required', 'string', 'max:4000'],
        ];
    }
}
