<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Image Processing Driver
    |--------------------------------------------------------------------------
    |
    | Supported drivers: 'gd', 'imagick'
    |
    | Imagick provides better quality and more format support,
    | but GD is more widely available and uses less memory.
    |
    */
    'driver' => env('IMAGE_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Default Quality Settings
    |--------------------------------------------------------------------------
    |
    | Default quality levels for image optimization and conversion.
    | Higher quality = larger file size.
    |
    */
    'quality' => [
        'webp' => env('IMAGE_QUALITY_WEBP', 85),
        'avif' => env('IMAGE_QUALITY_AVIF', 65),
        'jpeg' => env('IMAGE_QUALITY_JPEG', 90),
        'png' => env('IMAGE_QUALITY_PNG', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Sizes
    |--------------------------------------------------------------------------
    |
    | Predefined thumbnail sizes for image processing.
    | Keys are used as size identifiers in file names.
    |
    */
    'thumbnails' => [
        'thumb' => [
            'width' => 150,
            'height' => 150,
            'crop' => true,
        ],
        'small' => [
            'width' => 320,
            'height' => 240,
            'crop' => false,
        ],
        'medium' => [
            'width' => 640,
            'height' => 480,
            'crop' => false,
        ],
        'large' => [
            'width' => 1280,
            'height' => 960,
            'crop' => false,
        ],
        'hero' => [
            'width' => 1920,
            'height' => 1080,
            'crop' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Image Formats
    |--------------------------------------------------------------------------
    |
    | File extensions that can be processed by the image processor.
    |
    */
    'supported_formats' => [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'avif',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Upload Dimensions
    |--------------------------------------------------------------------------
    |
    | Maximum dimensions for uploaded images.
    | Images larger than this will be automatically resized.
    |
    */
    'max_dimensions' => [
        'width' => env('IMAGE_MAX_WIDTH', 4096),
        'height' => env('IMAGE_MAX_HEIGHT', 4096),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-optimization
    |--------------------------------------------------------------------------
    |
    | Enable automatic optimization of uploaded images.
    |
    */
    'auto_optimize' => env('IMAGE_AUTO_OPTIMIZE', true),

    /*
    |--------------------------------------------------------------------------
    | Modern Format Conversion
    |--------------------------------------------------------------------------
    |
    | Automatically create WebP versions of uploaded images
    | for better performance in modern browsers.
    |
    */
    'create_webp' => env('IMAGE_CREATE_WEBP', true),

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Paths for storing processed images.
    |
    */
    'paths' => [
        'public' => 'public/uploads/images',
        'private' => 'private/uploads/images',
    ],
];