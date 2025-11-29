<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|max:50',
            'label' => 'nullable|string|max:100',
            'url' => 'required|url|max:2000',
            'is_public' => 'sometimes|boolean',
        ];
    }
}
