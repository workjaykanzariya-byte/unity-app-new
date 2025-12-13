<?php

namespace Intervention\Image;

class ImageManager
{
    public function make(string $path): Image
    {
        return Image::fromFile($path);
    }
}
