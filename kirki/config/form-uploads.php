<?php

/**
 * MIME types accepted per file-upload field `accept` preset.
 *
 * Keyed by the exact preset string stored on the field (e.g. `default`,
 * `.jpg, .jpeg, .png, .gif`) — matches the presets in the builder's
 * FileTypes settings panel.
 */
return [
    'accepted_mime_types' => [
        'default' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/ogg', 'video/quicktime', 'application/pdf', 'application/msword', 'text/plain'],
        '.doc, .pdf, .txt' => ['application/pdf', 'application/msword', 'text/plain'],
        '.mp4, .mov' => ['video/mp4', 'video/ogg', 'video/quicktime'],
        '.jpg, .jpeg, .png, .gif' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
    ],
];
