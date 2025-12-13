<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\UploadImageRequest;
use App\Models\File;
use App\Services\Media\ImageOptimizer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadsController extends BaseApiController
{
    public function __construct(private readonly ImageOptimizer $optimizer)
    {
    }

    public function store(UploadImageRequest $request)
    {
        $uploadedFiles = [];

        if ($request->hasFile('files')) {
            $uploadedFiles = $request->file('files');
        } elseif ($request->hasFile('file')) {
            $uploadedFiles = [$request->file('file')];
        } else {
            return $this->error('No file uploaded.', 422);
        }

        $items = [];

        foreach ($uploadedFiles as $uploadedFile) {
            try {
                $optimized = $this->optimizer->optimize($uploadedFile);
            } catch (\Throwable $e) {
                $status = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 422;

                return $this->error($e->getMessage(), $status);
            }

            $disk = 'uploads';
            $id = (string) Str::uuid();
            $storagePath = "uploads/images/{$id}.{$optimized['extension']}";

            $stored = Storage::disk($disk)->put($storagePath, file_get_contents($optimized['tmp_path']));
            @unlink($optimized['tmp_path']);

            if (! $stored) {
                return $this->error('Failed to save the optimized image.', 500);
            }

            $file = File::create([
                'id' => $id,
                'uploader_user_id' => optional($request->user())->id,
                'disk' => $disk,
                'path' => $storagePath,
                'mime_type' => $optimized['mime_type'],
                'size_bytes' => $optimized['size'],
                'width' => $optimized['width'],
                'height' => $optimized['height'],
                'duration' => null,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'status' => 'ready',
            ]);

            $items[] = [
                'id' => $file->id,
                'status' => $file->status,
                'mime_type' => $file->mime_type,
                'size_bytes' => $file->size_bytes,
                'width' => $file->width,
                'height' => $file->height,
                'url' => route('api.files.show', ['id' => $file->id]),
            ];
        }

        return $this->success(['items' => $items], 'Files uploaded successfully', 201);
    }
}
