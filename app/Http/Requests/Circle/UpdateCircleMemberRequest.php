<?php

namespace App\Http\Requests\Circle;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCircleMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'sometimes|required|string|in:member,founder,director,chair,vice_chair,secretary,committee_leader',
            'status' => 'sometimes|required|string|in:pending,approved,rejected',
            'substitute_count' => 'sometimes|integer|min:0',
        ];
    }
}
