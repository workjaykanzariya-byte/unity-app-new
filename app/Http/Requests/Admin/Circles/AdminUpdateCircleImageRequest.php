<?php

namespace App\Http\Requests\Admin\Circles;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateCircleImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
