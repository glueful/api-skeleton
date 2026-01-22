<?php

/**
 * Application Configuration
 *
 * Core application settings, paths, performance, and pagination configurations.
 * Values can be overridden using environment variables.
 */

return [
    // Application Environment (development, staging, production)
    'env' => env('APP_ENV', 'development'),

    // Smart environment-aware debug default (false in production, true otherwise)
    'debug' => (bool) env('APP_DEBUG', env('APP_ENV') !== 'production'),

    // Smart environment-aware API documentation (disabled in production for security)
    'api_docs_enabled' => env('API_DOCS_ENABLED', env('APP_ENV') !== 'production'),

    // API Information
    'name' => env('APP_NAME', 'Glueful'),
    'urls' => [
        'base' => env('BASE_URL', 'http://localhost'),
        'docs' => rtrim(env('BASE_URL', 'http://localhost'), '/') . '/api/v' . env('API_VERSION', '1') . '/docs/',
    ]

];
