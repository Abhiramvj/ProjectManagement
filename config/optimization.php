<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Optimization Settings
    |--------------------------------------------------------------------------
    |
    | This file contains optimization settings for the application.
    |
    */

    'cache' => [
        'enabled' => env('CACHE_ENABLED', true),
        'ttl' => env('CACHE_TTL', 3600), // 1 hour
    ],

    'database' => [
        'query_logging' => env('DB_QUERY_LOGGING', false),
        'connection_limit' => env('DB_CONNECTION_LIMIT', 10),
    ],

    'assets' => [
        'minify' => env('ASSETS_MINIFY', true),
        'version' => env('ASSETS_VERSION', true),
        'gzip' => env('ASSETS_GZIP', true),
    ],

    'queue' => [
        'default' => env('QUEUE_CONNECTION', 'redis'),
        'retry_after' => env('QUEUE_RETRY_AFTER', 90),
        'max_attempts' => env('QUEUE_MAX_ATTEMPTS', 3),
    ],

    'session' => [
        'driver' => env('SESSION_DRIVER', 'redis'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
    ],

    'compression' => [
        'enabled' => env('COMPRESSION_ENABLED', true),
        'level' => env('COMPRESSION_LEVEL', 6),
    ],
]; 