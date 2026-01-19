<?php

namespace App\Http\Requests\Admin\Circles;

use App\Models\CircleMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCircleMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(CircleMember::roleOptions())],
        ];
    }
}
