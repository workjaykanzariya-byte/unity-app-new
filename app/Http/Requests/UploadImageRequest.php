<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSizeKb = (int) (config('media.max_image_upload_mb', 10) * 1024);

        return [
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:'.$maxSizeKb,
            ],
        ];
    }
}
