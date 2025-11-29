<?php

namespace App\Http\Requests\Referral;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitorLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'string', 'in:visited,signed_up,upgraded,joined_circle'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'converted_user_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'converted_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
