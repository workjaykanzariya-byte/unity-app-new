<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function show(string $id)
    {
        $file = File::findOrFail($id);

        $disk = $file->disk ?? config('filesystems.default');
        $path = $file->path ?? $file->s3_key;

        if (! $path) {
            abort(404, 'File not found on disk');
        }

        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'File not found on disk');
        }

        $absolutePath = Storage::disk($disk)->path($path);

        // To make local files publicly accessible, ensure `php artisan storage:link` has been
        // executed and the web server points to the Laravel public/ directory.
        return response()->file($absolutePath);
    }
}
