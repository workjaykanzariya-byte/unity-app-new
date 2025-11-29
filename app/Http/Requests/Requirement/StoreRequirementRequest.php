<?php

namespace App\Http\Requests\Requirement;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'media' => ['sometimes', 'array'],
            'media.*' => ['array'],
            'region_filter' => ['sometimes', 'array'],
            'category_filter' => ['sometimes', 'array'],
        ];
    }
}
