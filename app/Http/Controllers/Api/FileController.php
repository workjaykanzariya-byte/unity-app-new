<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\FileModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends BaseApiController
{
    /**
     * Serve a file by its UUID.
     */
    public function show(string $id)
    {
        $file = File::findOrFail($id);

        $disk = config('filesystems.default', 'public');

        if (! $file->s3_key || ! Storage::disk($disk)->exists($file->s3_key)) {
            abort(404, 'File not found');
        }

        $mime = $file->mime_type
            ?: Storage::disk($disk)->mimeType($file->s3_key)
            ?: 'application/octet-stream';

        return Storage::disk($disk)->response(
            $file->s3_key,
            null,
            [
                'Content-Type'  => $mime,
                'Cache-Control' => 'public, max-age=31536000',
            ]
        );
    }

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

        $model->refresh();

        return $this->success(new FileResource($model), 'File uploaded successfully', 201);
    }
}
