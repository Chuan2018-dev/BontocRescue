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

    'ai_severity' => [
        'enabled' => env('AI_SEVERITY_ENABLED', true),
        'url' => env(
            'AI_SEVERITY_SERVICE_URL',
            'http://'
            .env('AI_SEVERITY_SERVICE_HOST', '127.0.0.1')
            .':'
            .env('AI_SEVERITY_SERVICE_PORT', '8100')
        ),
        'timeout' => (int) env('AI_SEVERITY_TIMEOUT', 20),
        'dispatch' => env('AI_SEVERITY_DISPATCH', 'sync'),
        'model_name' => env('AI_SEVERITY_MODEL_NAME', 'bontoc_southern_leyte_production_candidate_external'),
        'model_version' => env('AI_SEVERITY_MODEL_VERSION', '0.2.0'),
    ],

    'routing' => [
        'enabled' => env('ROUTING_SERVICE_ENABLED', env('APP_ENV') !== 'testing'),
        'url' => env('ROUTING_SERVICE_URL', 'https://router.project-osrm.org'),
        'profile' => env('ROUTING_SERVICE_PROFILE', 'driving'),
        'timeout' => (int) env('ROUTING_SERVICE_TIMEOUT', 8),
        'cache_ttl_minutes' => (int) env('ROUTING_SERVICE_CACHE_TTL_MINUTES', 10),
        'provider' => env('ROUTING_SERVICE_PROVIDER', 'OSRM Public Routing'),
    ],

];
