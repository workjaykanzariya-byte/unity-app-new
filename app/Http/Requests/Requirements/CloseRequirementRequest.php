<?php

namespace App\Http\Requests\Requirements;

use Illuminate\Foundation\Http\FormRequest;

class CloseRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'close_type' => ['required', 'in:closed,completed'],
        ];
    }
}
