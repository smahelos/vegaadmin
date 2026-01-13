<?php

return [
    // Global default limits & settings applied when no specific context provided.
    'default' => [
        // Maximum file size in KB (10 MB default)
        'max_kb' => env('FILE_UPLOAD_MAX_KB', 10240),
        // Allowed filename extensions (lowercase, without dots). Empty array means allow all (NOT recommended in production).
        'allowed_extensions' => ['jpg','jpeg','png','webp','pdf'],
        // Allowed MIME prefix groups (e.g. image/ will allow image/png, image/jpeg). Empty array skips MIME prefix filtering.
        'allowed_mime_groups' => ['image/','application/pdf'],
        // Thumbnail generation defaults.
        'thumbnail' => [
            'enabled' => env('FILE_UPLOAD_THUMBNAIL_ENABLED', true),
            'width' => env('FILE_UPLOAD_THUMBNAIL_WIDTH', 200),
            'height' => env('FILE_UPLOAD_THUMBNAIL_HEIGHT', 200),
            'path' => env('FILE_UPLOAD_THUMBNAIL_PATH', 'thumbnails'),
            'quality' => env('FILE_UPLOAD_THUMBNAIL_QUALITY', 85),
        ],
        // Hash-based deduplication (global default off)
        'hash_deduplication' => [
            'enabled' => env('FILE_UPLOAD_HASH_DEDUP_ENABLED', false),
        ],
    ],

    // Optional per-context overrides. Key is an arbitrary context identifier passed from callers (e.g. 'invoice_logo').
    'contexts' => [
        'invoice_logo' => [
            // Align with request validation rule `max:2048` (in KB)
            'max_kb' => 2048, // 2 MB
            'allowed_extensions' => ['png','jpg','jpeg','gif','svg','webp'],
            'allowed_mime_groups' => ['image/'],
            'thumbnail' => [
                'enabled' => true,
                'width' => 300,
                'height' => 300,
                'path' => 'thumbnails',
                'quality' => 85,
            ],
            'hash_deduplication' => [ 'enabled' => true ],
        ],
        'supplier_logo' => [
            'max_kb' => 2048, // 2 MB
            'allowed_extensions' => ['png','jpg','jpeg','webp'],
            'allowed_mime_groups' => ['image/'],
            'thumbnail' => [
                'enabled' => true,
                'width' => 300,
                'height' => 300,
                'path' => 'thumbnails',
                'quality' => 85,
            ],
            'hash_deduplication' => [ 'enabled' => true ],
        ],
        'product_image' => [
            'max_kb' => 2048, // 2 MB
            'allowed_extensions' => ['jpg','jpeg','png','gif','webp'],
            'allowed_mime_groups' => ['image/'],
            'thumbnail' => [
                'enabled' => true,
                'width' => 200,
                'height' => 200,
                'path' => 'thumbnails',
                'quality' => 85,
            ],
            'hash_deduplication' => [ 'enabled' => true ],
        ],
        'attachment' => [
            'max_kb' => 20480, // 20 MB
            // Preserve legacy allowance: images + office docs + pdf + txt + archives
            'allowed_extensions' => [
                'jpg','jpeg','png','gif','webp',
                'pdf','doc','docx','xls','xlsx','csv','txt','zip'
            ],
            'allowed_mime_groups' => [
                'image/','application/pdf','application/msword','application/vnd','text/plain','text/csv','application/zip'
            ],
            'thumbnail' => [
                'enabled' => false,
            ],
            'hash_deduplication' => [ 'enabled' => true ],
        ],
    ],
];
