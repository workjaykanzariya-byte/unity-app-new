<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['array'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            if (! $this->input('content') && ! $this->input('attachments')) {
                $v->errors()->add('content', 'Either content or attachments is required.');
            }
        });
    }
}
