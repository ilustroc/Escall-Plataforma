<?php
return [
    'name' => env('APP_NAME', 'Plataforma ESCALL'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_ENV', 'production') !== 'production' && (bool)env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'America/Lima',
    'locale' => 'es', 'fallback_locale' => 'en', 'faker_locale' => 'es_PE',
    'key' => env('APP_KEY'), 'cipher' => 'AES-256-CBC',
    'previous_keys' => array_filter(explode(',', (string)env('APP_PREVIOUS_KEYS', ''))),
    'maintenance' => ['driver'=>'file'],
];