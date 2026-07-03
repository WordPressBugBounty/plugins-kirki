<?php

return [
    'supports' => [
        'image' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        'video' => ['video/mp4', 'video/ogg', 'video/quicktime'],
        'svg' => ['image/svg+xml'],
        'audio' => ['audio/mpeg', 'audio/ogg'],
        'json' => ['application/json'],
        'lottie' => ['text/plain', 'application/json'],
        'pdf' => ['application/pdf'],
    ]
];