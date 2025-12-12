<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FilesController extends BaseApiController
{
    public function show(string $id)
    {
        $file = File::findOrFail($id);

        $disk = $file->disk ?? config('filesystems.default', 'uploads');

        if (! $file->path || ! Storage::disk($disk)->exists($file->path)) {
            return $this->error('File not found.', 404);
        }

        $mime = $file->mime_type ?: Storage::disk($disk)->mimeType($file->path) ?: 'application/octet-stream';

        return Storage::disk($disk)->response($file->path, null, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
