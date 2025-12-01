<?php

namespace App\Http\Requests\Circle;

use Illuminate\Foundation\Http\FormRequest;

class StoreCircleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:200',
            // slug removed — auto-generated in Circle model
            'description'   => 'nullable|string',
            'purpose'       => 'nullable|string',
            'announcement'  => 'nullable|string',
            'template_id'   => 'nullable|uuid|exists:circle_templates,id',
            'city_id'       => 'nullable|uuid|exists:cities,id',

            'industry_tags'     => 'sometimes|array',
            'industry_tags.*'   => 'string|max:150',

            'calendar'      => 'sometimes|array',
        ];
    }
}
