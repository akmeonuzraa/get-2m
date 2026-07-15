<?php

return [
    // Single origin, or '*' to allow all (not recommended in production)
    'allowed_origin' => env('CORS_ALLOWED_ORIGIN', null),
    // Comma-separated list of allowed origins (preferred)
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', null),
    'allow_methods' => env('CORS_ALLOW_METHODS', 'GET, POST, PUT, PATCH, DELETE, OPTIONS'),
    'allow_headers' => env('CORS_ALLOW_HEADERS', 'Content-Type, Authorization, X-Requested-With, Accept'),
];
