<?php

namespace App\Http\Requests\Requirement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'media' => ['sometimes', 'array'],
            'media.*' => ['array'],
            'region_filter' => ['sometimes', 'array'],
            'category_filter' => ['sometimes', 'array'],
            'status' => ['sometimes', 'required', 'string', 'in:open,closed,archived'],
        ];
    }
}
