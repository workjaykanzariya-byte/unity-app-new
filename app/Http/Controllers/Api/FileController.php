<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\FileResource;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FileController extends BaseApiController
{
    public function upload(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $uploadedFile = $request->file('file');
        $disk = 'public';

        $path = $uploadedFile->store('uploads', $disk);

        $mimeType = $uploadedFile->getClientMimeType();
        $sizeBytes = $uploadedFile->getSize();
        $width = null;
        $height = null;
        $duration = null;

        if (str_starts_with((string) $mimeType, 'image/')) {
            try {
                $imageSize = getimagesize($uploadedFile->getRealPath());
                if ($imageSize) {
                    $width = $imageSize[0] ?? null;
                    $height = $imageSize[1] ?? null;
                }
            } catch (\Throwable $e) {
                // ignore errors reading image dimensions
            }
        }

        $model = File::create([
            'id' => (string) Str::uuid(),
            'uploader_user_id' => $user ? $user->id : null,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $mimeType,
            'size' => $sizeBytes,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
            's3_key' => $path,
            'size_bytes' => $sizeBytes,
        ]);

        $model->refresh();

        return $this->success(new FileResource($model), 'File uploaded successfully', 201);
    }
}
