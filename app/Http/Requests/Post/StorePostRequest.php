<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'circle_id' => 'nullable|uuid|exists:circles,id',
            'content_text' => 'nullable|string',
            'media' => 'nullable|array',
            'media.*' => 'array',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:150',
            'visibility' => 'required|string|in:public,circle,connections',
            'sponsored' => 'sometimes|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            if (! $this->input('content_text') && ! $this->input('media')) {
                $v->errors()->add('content_text', 'Either content_text or media is required.');
            }
        });
    }
}
