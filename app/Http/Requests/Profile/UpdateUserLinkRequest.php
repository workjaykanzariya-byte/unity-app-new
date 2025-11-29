<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|required|string|max:50',
            'label' => 'sometimes|nullable|string|max:100',
            'url' => 'sometimes|required|url|max:2000',
            'is_public' => 'sometimes|boolean',
        ];
    }
}
