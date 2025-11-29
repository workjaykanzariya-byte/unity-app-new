<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize input before validation.
     */
    protected function prepareForValidation(): void
    {
        // Treat "null" or "" as real null for optional fields
        if ($this->has('city_id') && ($this->city_id === 'null' || $this->city_id === '')) {
            $this->merge(['city_id' => null]);
        }

        if ($this->has('phone') && $this->phone === '') {
            $this->merge(['phone' => null]);
        }

        if ($this->has('last_name') && $this->last_name === '') {
            $this->merge(['last_name' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],

            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],

            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // password_confirmation is checked by "confirmed"

            // Optional, but if present must be a valid city UUID
            'city_id' => ['nullable', 'uuid', 'exists:cities,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'city_id.uuid'   => 'The city id field must be a valid UUID.',
            'city_id.exists' => 'The selected city does not exist.',
        ];
    }
}
