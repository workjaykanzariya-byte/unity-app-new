<?php

namespace App\Http\Requests\Circle;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCircleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:200',
            'description' => 'sometimes|string|nullable',
            'purpose' => 'sometimes|string|nullable',
            'announcement' => 'sometimes|string|nullable',
            'city_id' => 'sometimes|uuid|exists:cities,id|nullable',
            'industry_tags' => 'sometimes|array',
            'industry_tags.*' => 'string|max:150',
            'calendar' => 'sometimes|array',
        ];
    }
}
