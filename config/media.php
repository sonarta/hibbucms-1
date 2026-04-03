<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default disk for new uploads (Media::upload)
    |--------------------------------------------------------------------------
    |
    | Use "public" for local storage (storage/app/public), or "s3" after
    | configuring AWS credentials. See config/filesystems.php disks.s3.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Image variants (thumbnails / responsive sizes)
    |--------------------------------------------------------------------------
    |
    | Generated after upload for raster images. SVG and GIF are skipped.
    | Each preset scales down if wider than "width" (height auto).
    |
    */

    'variants' => [
        'enabled' => env('MEDIA_IMAGE_VARIANTS', true),

        'jpeg_quality' => (int) env('MEDIA_VARIANT_JPEG_QUALITY', 82),

        'presets' => [
            'thumb' => ['width' => 300],
            'medium' => ['width' => 768],
            'large' => ['width' => 1920],
        ],
    ],

];
