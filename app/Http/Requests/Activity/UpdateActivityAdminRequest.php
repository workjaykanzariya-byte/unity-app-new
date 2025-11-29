<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:approved,rejected',
            'coins_awarded' => 'nullable|integer|min:1',
            'admin_notes' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            if ($this->input('status') === 'approved' && ! $this->input('coins_awarded')) {
                $v->errors()->add('coins_awarded', 'coins_awarded is required when approving an activity.');
            }
        });
    }
}
