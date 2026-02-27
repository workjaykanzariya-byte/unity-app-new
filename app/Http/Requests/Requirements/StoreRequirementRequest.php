<?php

namespace App\Http\Requests\Requirements;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'uuid'],
            'category' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'media' => ['nullable', 'array'],
            'media.*.id' => ['nullable', 'uuid'],
            'media.*.url' => ['nullable', 'string'],
            'media.*.type' => ['nullable', 'string', 'max:50'],
            'region_filter' => ['nullable', 'array'],
            'region_filter.*' => ['string', 'max:100'],
            'category_filter' => ['nullable', 'array'],
            'category_filter.*' => ['string', 'max:100'],
        ];
    }
}
