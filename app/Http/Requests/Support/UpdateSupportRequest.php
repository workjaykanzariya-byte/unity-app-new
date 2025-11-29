<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'support_type' => ['sometimes', 'required', 'string', 'max:100'],
            'details' => ['sometimes', 'nullable', 'string'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['array'],
            'routed_to_user_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'status' => ['sometimes', 'required', 'string', 'in:open,in_progress,resolved,closed'],
        ];
    }
}
