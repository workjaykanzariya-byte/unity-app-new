<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\FileResource;
use App\Models\FileModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends BaseApiController
{
    public function upload(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');
        $disk = config('filesystems.default', 'public');

        $folder = 'uploads/' . now()->format('Y/m/d');
        $filename = (string) Str::uuid() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientOriginalName());

        $path = $file->storeAs($folder, $filename, $disk);

        $mimeType = $file->getClientMimeType();
        $sizeBytes = $file->getSize();
        $width = null;
        $height = null;
        $duration = null;

        if (str_starts_with((string) $mimeType, 'image/')) {
            try {
                $imageSize = getimagesize($file->getRealPath());
                if ($imageSize) {
                    $width = $imageSize[0] ?? null;
                    $height = $imageSize[1] ?? null;
                }
            } catch (\Throwable $e) {
                // ignore errors reading image dimensions
            }
        }

        $model = new FileModel();
        $model->uploader_user_id = $user ? $user->id : null;
        $model->s3_key = $path;
        $model->mime_type = $mimeType;
        $model->size_bytes = $sizeBytes;
        $model->width = $width;
        $model->height = $height;
        $model->duration = $duration;
        $model->save();

        return $this->success(new FileResource($model), 'File uploaded successfully', 201);
    }
}
