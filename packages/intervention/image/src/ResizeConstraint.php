<?php

namespace Intervention\Image;

class ResizeConstraint
{
    public bool $keepAspectRatio = false;
    public bool $preventUpsize = false;

    public function aspectRatio(): void
    {
        $this->keepAspectRatio = true;
    }

    public function upsize(): void
    {
        $this->preventUpsize = true;
    }
}
