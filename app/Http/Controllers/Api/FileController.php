<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\FileResource;
use App\Jobs\ProcessUploadedFile;
use App\Models\File;
use App\Models\FileModel;
use App\Services\Media\MediaProcessor;
use App\Support\Media\Probe;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Exceptions\MediaProcessingException;
use Illuminate\Support\Facades\Log;

class FileController extends BaseApiController
{
    public function __construct(
        private readonly MediaProcessor $mediaProcessor,
        private readonly Probe $probe
    ) {
    }

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
        $filesInput = $request->file('file');

        if (is_array($filesInput)) {
            $request->validate([
                'file' => ['required', 'array'],
                'file.*' => ['file', 'max:10240'],
            ]);

            $uploaded = [];

            foreach ($filesInput as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    continue;
                }

                try {
                    $model = $this->storeUploadedFile($file, $request->user());
                } catch (MediaProcessingException $e) {
                    return $this->error($e->getMessage(), 422);
                } catch (\Throwable $e) {
                    Log::error('File upload failed', ['error' => $e->getMessage()]);

                    return $this->error('File upload failed. Please try again.', 500);
                }

                $uploaded[] = new FileResource($model);
            }

            return $this->success($uploaded, 'Files uploaded successfully.', 201);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        if (! $filesInput instanceof UploadedFile) {
            return $this->error('Invalid file uploaded.', 422);
        }

        try {
            $model = $this->storeUploadedFile($filesInput, $request->user());
        } catch (MediaProcessingException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('File upload failed', ['error' => $e->getMessage()]);

            return $this->error('File upload failed. Please try again.', 500);
        }

        return $this->success(new FileResource($model), 'File uploaded successfully', 201);
    }

    private function storeUploadedFile(UploadedFile $file, $user): FileModel
    {
        $disk = config('filesystems.default', 'public');

        $folder = 'uploads/' . now()->format('Y/m/d');
        $filename = (string) Str::uuid() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientOriginalName());

        $path = $file->storeAs($folder, $filename, $disk);

        $mimeType = $file->getClientMimeType() ?: $this->probe->mimeType($file->getRealPath());
        $sizeBytes = $file->getSize();
        $width = null;
        $height = null;
        $duration = null;

        if ($this->probe->isImageMime($mimeType)) {
            $dimensions = $this->probe->imageDimensions($file->getRealPath());
            $width = $dimensions['width'];
            $height = $dimensions['height'];
        }

        if ($this->probe->isVideoMime($mimeType)) {
            $videoMeta = $this->probe->videoMetadata($file->getRealPath());
            $width = $videoMeta['width'];
            $height = $videoMeta['height'];
            $duration = $videoMeta['duration'];
        }

        $model = new FileModel();
        $model->uploader_user_id = $user ? $user->id : null;
        $model->s3_key = $path;
        $model->mime_type = $mimeType;
        $model->size_bytes = $sizeBytes;
        $model->width = $width;
        $model->height = $height;
        $model->duration = $duration;
        $model->meta = [
            'processing_status' => 'pending',
            'original_s3_key' => $path,
            'variants' => [],
        ];
        $model->save();

        $processingMode = config('media.processing.mode', 'sync');

        if ($processingMode === 'queue') {
            ProcessUploadedFile::dispatch($model->id);
            $model->meta = array_merge($model->meta ?? [], ['processing_status' => 'queued']);
            $model->save();

            return $model->refresh();
        }

        try {
            $processed = $this->mediaProcessor->process($model);
        } catch (MediaProcessingException $e) {
            $this->markProcessingFailure($model, $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $this->markProcessingFailure($model, 'Media processing failed.');
            Log::error('Media processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $processed->refresh();
    }

    private function markProcessingFailure(FileModel $model, string $message): void
    {
        $meta = $model->meta ?? [];
        $meta['processing_status'] = 'failed';
        $meta['processing_error'] = $message;
        $model->meta = $meta;
        $model->save();
    }
}
