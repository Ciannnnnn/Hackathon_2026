<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GEMINI_FALLBACK_MODELS', 'gemini-3.1-flash-lite')),
        ))),
        'demo_fallback' => env('AI_DEMO_FALLBACK', true),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 15),
        'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 2048),
    ],

    'ml' => [
        'enabled' => env('ML_SERVICE_ENABLED', true),
        'url' => env('ML_SERVICE_URL', 'http://localhost:5001'),
        'timeout' => (int) env('ML_SERVICE_TIMEOUT', 5),
    ],

    'rag' => [
        'driver' => env('RAG_EXTRACTION_DRIVER', 'local'),
        'enabled' => env('RAG_SERVICE_ENABLED', true),
        'url' => env('RAG_SERVICE_URL', 'http://localhost:5002'),
        'timeout' => (int) env('RAG_SERVICE_TIMEOUT', 20),
        'max_pdf_size_mb' => (int) env('MAX_PDF_SIZE_MB', 10),
        'max_module_size_mb' => (int) env('MAX_MODULE_SIZE_MB', env('MAX_PDF_SIZE_MB', 10)),
    ],

];
