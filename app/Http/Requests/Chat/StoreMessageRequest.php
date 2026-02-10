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
            'content_text' => ['nullable', 'string'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['array'],
            'files' => ['sometimes', 'array'],
            'files.*' => [
                'file',
                'max:51200',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,video/mp4,video/quicktime,video/webm,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $hasContent = filled($this->input('content')) || filled($this->input('content_text'));
            $hasAttachments = is_array($this->input('attachments')) && count($this->input('attachments')) > 0;
            $files = $this->file('files');
            $hasFiles = $files instanceof \Illuminate\Http\UploadedFile
                || (is_array($files) && count($files) > 0);

            if (! $hasContent && ! $hasAttachments && ! $hasFiles) {
                $v->errors()->add('content', 'Either content or attachments is required.');
            }
        });
    }
}
