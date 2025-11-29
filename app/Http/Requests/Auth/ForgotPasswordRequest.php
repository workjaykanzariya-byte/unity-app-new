<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                // Important: validate against users table
                'exists:users,email',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            // We do not reveal whether email exists for security,
            // but we override the validation error message.
            'email.exists' => 'If your email is registered, a reset link has been sent.',
        ];
    }
}
