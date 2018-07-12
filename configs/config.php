<?php
global $wpdb;
return [
    'tables' => [
        'sync' => $wpdb->prefix . 'blds_sync_map',
    ],

    'options' => [
        'access_token' => 'blds_access_token',
        'webhook_token' => 'blds_webhook_token',
        'syncedAt' => 'blds_synced_at'
    ],

    'paths' => [
        'base' => realpath(__DIR__ . '/../bigly-dropship.php')
    ],

    'remote' => [
        'base' => 'https://app.bigly.io',
        'sync' => 'api/sync',
        'webhook' => 'api/webhooks/register',
        'access_token' => 'oauth/token'
    ],
];
