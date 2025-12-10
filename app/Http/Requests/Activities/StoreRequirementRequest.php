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
            'region_label' => ['required', 'string', 'max:100'],
            'city_name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'budget' => ['nullable', 'numeric'],
            'timeline' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'visibility' => ['required', 'in:public,connections,private'],
            'status' => ['nullable', 'in:open,in_progress,closed'],
            'media' => ['nullable', 'array'],
            'media.*.id' => ['required_with:media', 'uuid', 'exists:files,id'],
            'media.*.type' => ['required_with:media', 'string', 'max:50'],
            'media_id' => ['nullable', 'uuid', 'exists:files,id'],
        ];
    }
}
