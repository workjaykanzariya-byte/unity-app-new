<?php

namespace App\Services\Media;

use Exception;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use RuntimeException;

class ImageOptimizer
{
    private ImageManager $imageManager;

    public function __construct()
    {
        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            throw new RuntimeException('Image processing not available. Enable GD or Imagick.', 500);
        }

        $driver = extension_loaded('imagick') ? 'imagick' : 'gd';

        $this->imageManager = new ImageManager(['driver' => $driver]);
    }

    public function optimize(UploadedFile $file): array
    {
        $maxMegapixels = (float) config('media.max_image_megapixels', 36);
        [$width, $height] = $this->getDimensions($file);

        $megapixels = ($width * $height) / 1_000_000;
        if ($megapixels > $maxMegapixels) {
            throw new Exception('Image is too large. Maximum allowed is '. $maxMegapixels .' megapixels.');
        }

        $image = $this->imageManager->make($file->getRealPath())->orientate();

        $maxWidth = (int) config('media.image_max_width', 1080);
        if ($image->width() > $maxWidth) {
            $image->resize($maxWidth, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $format = strtolower((string) config('media.image_format', 'webp'));
        $quality = (int) config('media.image_quality', 80);
        $mime = 'image/'.$format;

        try {
            $encoded = $image->encode($format, $quality);
        } catch (\Throwable $e) {
            $format = 'jpg';
            $mime = 'image/jpeg';
            $encoded = $image->encode($format, $quality);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'imgopt_');
        if ($tempPath === false) {
            throw new Exception('Failed to create temporary file for image.');
        }

        file_put_contents($tempPath, $encoded);

        [$finalWidth, $finalHeight] = $this->getImageSizeFromPath($tempPath);

        return [
            'tmp_path' => $tempPath,
            'mime_type' => $mime,
            'extension' => $format === 'jpeg' ? 'jpg' : $format,
            'size' => filesize($tempPath) ?: 0,
            'width' => $finalWidth,
            'height' => $finalHeight,
        ];
    }

    private function getDimensions(UploadedFile $file): array
    {
        $info = @getimagesize($file->getRealPath());
        if (! $info || ! isset($info[0], $info[1])) {
            throw new Exception('Unable to read image dimensions.');
        }

        return [$info[0], $info[1]];
    }

    private function getImageSizeFromPath(string $path): array
    {
        $info = @getimagesize($path);
        if (! $info || ! isset($info[0], $info[1])) {
            throw new Exception('Unable to read processed image dimensions.');
        }

        return [$info[0], $info[1]];
    }
}
