<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->filled('phone') ? $this->phone : null,
            'last_name' => $this->filled('last_name') ? $this->last_name : null,
            'display_name' => $this->filled('display_name') ? $this->display_name : null,
            'city_id' => $this->filled('city_id') ? $this->city_id : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city_id' => ['nullable'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
