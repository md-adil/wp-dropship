<?php
global $wpdb;
return [
    'tables' => [
        'sync' => $wpdb->prefix . 'blds_sync_map',
    ],

    'options' => [
        'access_token' => 'blds_access_token',
        'syncedAt' => 'blds_synced_at'
    ],

    'paths' => [
        'base' => realpath(__DIR__ . '/../bigly-dropship.php')
    ],

    'remote' => [
        'base' => 'http://dropship.bigly.io',
        'sync' => 'api/sync',
        'access_token' => 'oauth/token'
    ],
];
