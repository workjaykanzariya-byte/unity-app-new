<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_user_id' => ['required', 'uuid', 'exists:users,id'],
            'content' => ['required', 'string', 'max:2000'],
            'media' => ['nullable', 'array'],
            'media.*.id' => ['required_with:media', 'uuid', 'exists:files,id'],
            'media.*.type' => ['required_with:media', 'string', 'max:50'],
            'media_id' => ['nullable', 'uuid', 'exists:files,id'],
        ];
    }
}
