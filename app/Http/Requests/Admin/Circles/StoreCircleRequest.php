<?php

namespace App\Http\Requests\Admin\Circles;

use App\Models\Circle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCircleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'purpose' => ['nullable', 'string'],
            'announcement' => ['nullable', 'string'],
            'city_id' => ['required', 'uuid', 'exists:cities,id'],
            'type' => ['required', Rule::in(Circle::TYPE_OPTIONS)],
            'status' => ['nullable', Rule::in(Circle::STATUS_OPTIONS)],
            'industry_tags' => ['nullable'],
        ];
    }
}
