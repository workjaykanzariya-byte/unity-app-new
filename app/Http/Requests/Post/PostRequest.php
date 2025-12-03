<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'content' => 'nullable|string',
            'media_ids' => 'nullable|array',
            'media_ids.*' => 'uuid|exists:files,id',
        ];
    }
}
