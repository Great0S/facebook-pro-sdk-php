<?php

/**
 * Real Facebook App Configuration
 * Using actual App ID and Secret for production testing
 */

return [
    // Facebook App Credentials
    'app_id' => '',
    'app_secret' => '',
    'default_graph_version' => 'v19.0',

    // Configuration with proper nesting
    'app' => [
        'id' => '',
        'secret' => '',
        'version' => 'v19.0'
    ],

    // Cache settings
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'driver' => 'memory'
    ],

    // Logging settings
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'file' => sys_get_temp_dir() . '/facebook_sdk.log'
    ],

    // Rate limiting
    'rate_limit' => [
        'enabled' => true,
        'limit' => 200,
        'window' => 3600
    ],

    // Performance monitoring
    'performance' => [
        'monitoring' => true
    ],

    // Retry settings
    'retry' => [
        'max_attempts' => 3,
        'base_delay' => 1000
    ],

    // Webhook settings (optional)
    'webhook' => [
        'secret' => 'your_webhook_secret_here',
        'verify_token' => 'your_verify_token_here'
    ]
];
