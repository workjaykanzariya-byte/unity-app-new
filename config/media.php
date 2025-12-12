<?php

return [
    'max_image_upload_mb' => env('MEDIA_MAX_IMAGE_UPLOAD_MB', 10),
    'max_image_megapixels' => env('MEDIA_MAX_IMAGE_MEGAPIXELS', 36),
    'image_max_width' => env('MEDIA_IMAGE_MAX_WIDTH', 1080),
    'image_quality' => env('MEDIA_IMAGE_QUALITY', 80),
    'image_format' => env('MEDIA_IMAGE_FORMAT', 'webp'),
];
