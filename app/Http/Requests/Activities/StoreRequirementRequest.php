<?php

namespace App\Http\Requests\Activities;

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
            'description' => ['required', 'string'],
            'media_id' => ['nullable', 'uuid'],
            'region_label' => ['required', 'string', 'max:50'],
            'city_name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'in:open,in_progress,closed'],
        ];
    }
}
