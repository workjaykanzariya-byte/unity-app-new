<?php

namespace Intervention\Image;

use GdImage;
use InvalidArgumentException;
use RuntimeException;

class Image
{
    private GdImage $resource;

    public function __construct(GdImage $resource)
    {
        $this->resource = $resource;
    }

    public static function fromFile(string $path): self
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException('Image file not found.');
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to read image file.');
        }

        $resource = imagecreatefromstring($contents);
        if (! $resource instanceof GdImage) {
            throw new RuntimeException('Unable to create image from contents.');
        }

        return new self($resource);
    }

    public function orientate(): self
    {
        if (! function_exists('exif_read_data')) {
            return $this;
        }

        ob_start();
        imagejpeg($this->resource, null, 90);
        $binary = ob_get_clean();

        if ($binary === false) {
            return $this;
        }

        $data = @exif_read_data('data://image/jpeg;base64,'.base64_encode($binary));

        if (! $data || ! isset($data['Orientation'])) {
            return $this;
        }

        switch ($data['Orientation']) {
            case 3:
                $this->resource = imagerotate($this->resource, 180, 0);
                break;
            case 6:
                $this->resource = imagerotate($this->resource, -90, 0);
                break;
            case 8:
                $this->resource = imagerotate($this->resource, 90, 0);
                break;
        }

        return $this;
    }

    public function resize(?int $width, ?int $height, ?callable $callback = null): self
    {
        $constraint = new ResizeConstraint();
        if ($callback) {
            $callback($constraint);
        }

        $currentWidth = $this->width();
        $currentHeight = $this->height();

        $targetWidth = $width ?? $currentWidth;
        $targetHeight = $height ?? $currentHeight;

        if ($constraint->preventUpsize) {
            $targetWidth = min($targetWidth, $currentWidth);
            $targetHeight = min($targetHeight, $currentHeight);
        }

        if ($constraint->keepAspectRatio) {
            if ($width !== null && $height === null) {
                $ratio = $currentHeight / $currentWidth;
                $targetHeight = (int) round($targetWidth * $ratio);
            } elseif ($width === null && $height !== null) {
                $ratio = $currentWidth / $currentHeight;
                $targetWidth = (int) round($targetHeight * $ratio);
            } else {
                $ratio = min($targetWidth / $currentWidth, $targetHeight / $currentHeight);
                $targetWidth = (int) round($currentWidth * $ratio);
                $targetHeight = (int) round($currentHeight * $ratio);
            }
        }

        if ($targetWidth <= 0 || $targetHeight <= 0) {
            return $this;
        }

        if ($targetWidth === $currentWidth && $targetHeight === $currentHeight) {
            return $this;
        }

        $resized = imagescale($this->resource, $targetWidth, $targetHeight, IMG_BICUBIC);

        if ($resized instanceof GdImage) {
            $this->resource = $resized;
        }

        return $this;
    }

    public function encode(string $format = 'webp', int $quality = 90): string
    {
        $format = strtolower($format);
        ob_start();

        switch ($format) {
            case 'webp':
                if (! function_exists('imagewebp')) {
                    throw new RuntimeException('WEBP support is not available.');
                }
                imagewebp($this->resource, null, $quality);
                $mime = 'image/webp';
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($this->resource, null, $quality);
                $mime = 'image/jpeg';
                break;
            case 'png':
                imagepng($this->resource, null, (int) round(9 - ($quality / 10)));
                $mime = 'image/png';
                break;
            default:
                ob_end_clean();
                throw new InvalidArgumentException('Unsupported image format: '.$format);
        }

        $data = ob_get_clean();
        if ($data === false) {
            throw new RuntimeException('Failed to encode image.');
        }

        return $data;
    }

    public function width(): int
    {
        return imagesx($this->resource);
    }

    public function height(): int
    {
        return imagesy($this->resource);
    }
}
