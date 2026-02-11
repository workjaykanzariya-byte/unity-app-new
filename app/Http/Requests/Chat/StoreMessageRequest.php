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
            'content' => ['nullable', 'string', 'max:5000'],
            'content_text' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['array'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => [
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,video/mp4,video/quicktime,video/webm,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $content = $this->normalizeContent(
                $this->input('content_text', $this->input('content'))
            );

            $this->merge([
                'content' => $content,
                'content_text' => $content,
            ]);

            $hasContent = filled($content);
            $hasAttachments = is_array($this->input('attachments')) && count($this->input('attachments')) > 0;
            $files = $this->file('files');
            $hasFiles = $files instanceof \Illuminate\Http\UploadedFile
                || (is_array($files) && count($files) > 0);

            if (! $hasContent && ! $hasAttachments && ! $hasFiles) {
                $v->errors()->add('content', 'Either content or files is required.');
            }
        });
    }

    private function normalizeContent(mixed $content): ?string
    {
        if (! is_string($content)) {
            return null;
        }

        $trimmed = trim($content);
        if ($trimmed === '') {
            return null;
        }

        $lowered = mb_strtolower($trimmed);
        if (in_array($lowered, ['none', 'null'], true)) {
            return null;
        }

        return $trimmed;
    }
}
