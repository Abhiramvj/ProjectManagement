<?php

// Corrected Config for cloudstudio/ollama-laravel

return [

    'model' => env('OLLAMA_MODEL', 'llama3'),

    'url' => env('OLLAMA_URL', 'http://127.0.0.1'),

    'port' => env('OLLAMA_PORT', 11434),

    'request_timeout' => env('OLLAMA_REQUEST_TIMEOUT', 300),
];
