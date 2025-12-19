<?php

namespace App\Services\Media;

use App\Exceptions\MediaProcessingException;
use App\Models\FileModel;
use App\Support\Media\Probe;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class MediaProcessor
{
    public function __construct(private readonly Probe $probe)
    {
    }

    public function process(FileModel $file): FileModel
    {
        $disk = config('filesystems.default', 'public');
        $path = $file->s3_key;

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            throw new MediaProcessingException('File missing from storage.');
        }

        $source = $this->resolveSourcePath($disk, $path);
        $meta = $file->meta ?? [];
        $meta['original_s3_key'] = $meta['original_s3_key'] ?? $path;
        $meta['processing_status'] = $meta['processing_status'] ?? 'pending';
        $meta['variants'] = $meta['variants'] ?? [];

        $file->meta = $meta;
        $file->save();

        try {
            $mime = $file->mime_type ?: $this->probe->mimeType($source['path']);

            if ($this->probe->isImageMime($mime)) {
                $result = $this->processImage($file, $disk, $source['path'], $mime);
            } elseif ($this->probe->isVideoMime($mime)) {
                $result = $this->processVideo($file, $disk, $source['path']);
            } else {
                $file->meta = $this->markCompleted($meta);
                $file->save();

                return $file->refresh();
            }
        } finally {
            $this->cleanupTemporary($source);
        }

        $file->s3_key = $result['s3_key'];
        $file->mime_type = $result['mime_type'];
        $file->size_bytes = $result['size_bytes'];
        $file->width = $result['width'];
        $file->height = $result['height'];
        $file->duration = $result['duration'];
        $file->meta = $this->markCompleted(array_merge($meta, [
            'variants' => $result['variants'],
        ]));
        $file->save();

        $this->cleanupOriginal($disk, $meta['original_s3_key'], $result['s3_key']);

        return $file->refresh();
    }

    private function processImage(FileModel $file, string $disk, string $sourcePath, ?string $mime): array
    {
        if (! $this->probe->imagickAvailable() && ! $this->probe->gdAvailable()) {
            throw new MediaProcessingException('Image processing requires Imagick or GD extensions.');
        }

        $maxWidth = (int) config('media.image.max_width', 1600);
        $maxHeight = (int) config('media.image.max_height', 1600);
        $quality = (int) config('media.image.quality', 80);
        $thumbMax = (int) config('media.image.thumbnail_max_width', 400);

        $hasAlpha = $this->imageHasAlpha($sourcePath, $mime);
        $preferred = config('media.image.format_preference', 'webp');
        $webpSupported = $this->webpSupported();

        $targetFormat = $this->decideImageFormat($preferred, $webpSupported, $hasAlpha);
        $targetMime = $this->mimeForFormat($targetFormat);

        $optimizedKey = $this->buildPath($file->s3_key, 'optimized', $targetFormat);
        $optimizedTemp = $this->tempFilePath($optimizedKey);
        [$width, $height] = $this->resizeImage($sourcePath, $optimizedTemp, $targetFormat, $maxWidth, $maxHeight, $quality, $mime);

        $thumbKey = $this->buildPath($file->s3_key, 'thumb', $targetFormat);
        $thumbTemp = $this->tempFilePath($thumbKey);
        $this->resizeImage($sourcePath, $thumbTemp, $targetFormat, $thumbMax, $thumbMax, $quality, $mime);

        $this->putFile($disk, $optimizedKey, $optimizedTemp);
        $this->putFile($disk, $thumbKey, $thumbTemp);

        $variants = [
            'thumbnail' => $thumbKey,
        ];

        $sizeBytes = @filesize($optimizedTemp) ?: null;

        @unlink($optimizedTemp);
        @unlink($thumbTemp);

        return [
            's3_key' => $optimizedKey,
            'mime_type' => $targetMime,
            'size_bytes' => $sizeBytes,
            'width' => $width,
            'height' => $height,
            'duration' => null,
            'variants' => $variants,
        ];
    }

    private function processVideo(FileModel $file, string $disk, string $sourcePath): array
    {
        if (! $this->probe->ffmpegAvailable()) {
            throw new MediaProcessingException('FFmpeg is required for video processing.');
        }

        $maxWidth = (int) config('media.video.max_width', 1280);
        $crf = (string) config('media.video.crf', '28');
        $preset = (string) config('media.video.preset', 'veryfast');
        $audioBitrate = (string) config('media.video.audio_bitrate', '128k');
        $posterWidth = (int) config('media.video.poster_max_width', 800);
        $posterSecond = (int) config('media.video.poster_second', 1);

        $targetFormat = 'mp4';
        $optimizedKey = $this->buildPath($file->s3_key, 'optimized', $targetFormat);
        $optimizedTemp = $this->tempFilePath($optimizedKey);

        $scaleFilter = 'scale=min(' . $maxWidth . ',iw):-2';

        $process = new Process([
            'ffmpeg',
            '-y',
            '-i',
            $sourcePath,
            '-vf',
            $scaleFilter,
            '-c:v',
            'libx264',
            '-preset',
            $preset,
            '-crf',
            (string) $crf,
            '-c:a',
            'aac',
            '-b:a',
            $audioBitrate,
            '-movflags',
            '+faststart',
            $optimizedTemp,
        ]);

        $process->setTimeout((int) config('media.video.timeout', 120));
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('FFmpeg failed', ['output' => $process->getErrorOutput()]);
            @unlink($optimizedTemp);
            throw new MediaProcessingException('Video transcoding failed.');
        }

        // Poster image
        $posterKey = $this->buildPath($file->s3_key, 'poster', 'jpg');
        $posterTemp = $this->tempFilePath($posterKey);

        $posterProcess = new Process([
            'ffmpeg',
            '-y',
            '-i',
            $optimizedTemp,
            '-ss',
            sprintf('00:00:%02d', $posterSecond),
            '-vframes',
            '1',
            '-vf',
            'scale=min(' . $posterWidth . ',iw):-2',
            $posterTemp,
        ]);

        $posterProcess->setTimeout(20);
        $posterProcess->run();

        if (! $posterProcess->isSuccessful()) {
            Log::warning('Poster generation failed', ['output' => $posterProcess->getErrorOutput()]);
        }

        $meta = $this->probe->videoMetadata($optimizedTemp);

        $this->putFile($disk, $optimizedKey, $optimizedTemp);
        if ($posterProcess->isSuccessful()) {
            $this->putFile($disk, $posterKey, $posterTemp);
        }

        $variants = [
            'poster' => $posterProcess->isSuccessful() ? $posterKey : null,
        ];

        $sizeBytes = @filesize($optimizedTemp) ?: null;

        @unlink($optimizedTemp);
        @unlink($posterTemp);

        return [
            's3_key' => $optimizedKey,
            'mime_type' => 'video/mp4',
            'size_bytes' => $sizeBytes,
            'width' => $meta['width'] ?? null,
            'height' => $meta['height'] ?? null,
            'duration' => $meta['duration'] ?? null,
            'variants' => $variants,
        ];
    }

    private function resizeImage(string $source, string $destination, string $format, int $maxWidth, int $maxHeight, int $quality, ?string $mime): array
    {
        if ($this->probe->imagickAvailable()) {
            return $this->resizeWithImagick($source, $destination, $format, $maxWidth, $maxHeight, $quality);
        }

        return $this->resizeWithGd($source, $destination, $format, $maxWidth, $maxHeight, $quality, $mime);
    }

    private function resizeWithImagick(string $source, string $destination, string $format, int $maxWidth, int $maxHeight, int $quality): array
    {
        try {
            $image = new \Imagick($source);
        } catch (\Throwable $e) {
            Log::warning('Imagick failed to read image', ['error' => $e->getMessage()]);
            throw new MediaProcessingException('Unable to read image with Imagick.');
        }

        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        }

        $image->stripImage();

        [$newWidth, $newHeight] = $this->fitWithin($image->getImageWidth(), $image->getImageHeight(), $maxWidth, $maxHeight);
        $image->thumbnailImage($newWidth, $newHeight, true);

        $image->setImageFormat($format === 'jpg' ? 'jpeg' : $format);
        $image->setImageCompressionQuality($quality);

        $this->ensureDirectory($destination);
        $image->writeImage($destination);
        $image->clear();
        $image->destroy();

        return [$newWidth, $newHeight];
    }

    private function resizeWithGd(string $source, string $destination, string $format, int $maxWidth, int $maxHeight, int $quality, ?string $mime): array
    {
        $contents = @file_get_contents($source);
        if ($contents === false) {
            throw new MediaProcessingException('Unable to read image for optimization.');
        }

        $image = @imagecreatefromstring($contents);
        if (! $image) {
            throw new MediaProcessingException('Invalid image data.');
        }

        $width = imagesx($image) ?: 1;
        $height = imagesy($image) ?: 1;
        [$newWidth, $newHeight] = $this->fitWithin($width, $height, $maxWidth, $maxHeight);

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency when possible.
        if ($this->shouldKeepAlpha($format, $mime)) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $this->ensureDirectory($destination);

        switch ($format) {
            case 'webp':
                if (! function_exists('imagewebp')) {
                    throw new MediaProcessingException('WEBP encoding is not available in GD.');
                }
                imagewebp($resized, $destination, $quality);
                break;
            case 'png':
                imagepng($resized, $destination, 6);
                break;
            default:
                imagejpeg($resized, $destination, $quality);
                break;
        }

        imagedestroy($resized);
        imagedestroy($image);

        return [$newWidth, $newHeight];
    }

    private function fitWithin(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        $width = max(1, $width);
        $height = max(1, $height);

        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);

        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        return [max(1, $newWidth), max(1, $newHeight)];
    }

    private function buildPath(string $originalKey, string $suffix, string $extension): string
    {
        $dir = pathinfo($originalKey, PATHINFO_DIRNAME);
        $base = pathinfo($originalKey, PATHINFO_FILENAME);

        return trim($dir, '/') . '/' . $base . '_' . $suffix . '.' . $extension;
    }

    private function markCompleted(array $meta): array
    {
        $meta['processing_status'] = 'completed';
        $meta['processing_error'] = null;

        return $meta;
    }

    private function cleanupOriginal(string $disk, ?string $originalKey, string $optimizedKey): void
    {
        $keepOriginal = (bool) config('media.keep_original', false);

        if ($keepOriginal || ! $originalKey || $originalKey === $optimizedKey) {
            return;
        }

        if (Storage::disk($disk)->exists($originalKey)) {
            Storage::disk($disk)->delete($originalKey);
        }
    }

    private function decideImageFormat(string $preferred, bool $webpSupported, bool $hasAlpha): string
    {
        if ($hasAlpha && $preferred === 'webp' && $webpSupported) {
            return 'webp';
        }

        if ($hasAlpha) {
            return 'png';
        }

        if ($preferred === 'webp' && $webpSupported) {
            return 'webp';
        }

        return 'jpg';
    }

    private function mimeForFormat(string $format): string
    {
        return match ($format) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            default => 'image/jpeg',
        };
    }

    private function ensureDirectory(string $absolutePath): void
    {
        $directory = pathinfo($absolutePath, PATHINFO_DIRNAME);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    private function ensureStorageDirectory(string $disk, string $key): void
    {
        $directory = trim(pathinfo($key, PATHINFO_DIRNAME), '/');
        if ($directory && method_exists(Storage::disk($disk), 'makeDirectory')) {
            Storage::disk($disk)->makeDirectory($directory);
        }
    }

    private function webpSupported(): bool
    {
        if ($this->probe->imagickAvailable()) {
            try {
                $formats = (new \Imagick())->queryFormats('WEBP');

                return ! empty($formats);
            } catch (\Throwable) {
                return false;
            }
        }

        return function_exists('imagewebp');
    }

    private function imageHasAlpha(string $path, ?string $mime): bool
    {
        if ($this->probe->imagickAvailable()) {
            try {
                $image = new \Imagick($path);
                $hasAlpha = $image->getImageAlphaChannel();
                $image->clear();
                $image->destroy();

                return (bool) $hasAlpha;
            } catch (\Throwable) {
                // fall back to mime-based detection
            }
        }

        // Basic heuristic: PNG/WebP often support alpha
        return in_array($mime, ['image/png', 'image/webp'], true);
    }

    private function shouldKeepAlpha(string $format, ?string $mime): bool
    {
        if ($format === 'png' || $format === 'webp') {
            return true;
        }

        return $mime === 'image/png';
    }

    private function resolveSourcePath(string $disk, string $path): array
    {
        $adapter = Storage::disk($disk);

        try {
            return [
                'path' => $adapter->path($path),
                'temporary' => false,
            ];
        } catch (\Throwable) {
            $local = $this->tempFilePath($path);
            $this->ensureDirectory($local);

            $stream = $adapter->readStream($path);
            if (! $stream) {
                throw new MediaProcessingException('Unable to read file from storage.');
            }

            $destination = fopen($local, 'w+b');
            stream_copy_to_stream($stream, $destination);
            fclose($stream);
            fclose($destination);

            return [
                'path' => $local,
                'temporary' => true,
            ];
        }
    }

    private function cleanupTemporary(array $file): void
    {
        if (($file['temporary'] ?? false) && isset($file['path']) && is_file($file['path'])) {
            @unlink($file['path']);
        }
    }

    private function tempFilePath(string $originalKey): string
    {
        $dir = $this->processingTempDirectory();
        $extension = pathinfo($originalKey, PATHINFO_EXTENSION);
        $extension = $extension ? '.' . $extension : '';

        return $dir . '/' . uniqid('media_', true) . $extension;
    }

    private function processingTempDirectory(): string
    {
        $dir = storage_path('app/tmp/media');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function putFile(string $disk, string $key, string $localPath): void
    {
        $this->ensureStorageDirectory($disk, $key);

        $stream = fopen($localPath, 'r');
        Storage::disk($disk)->put($key, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }
}
