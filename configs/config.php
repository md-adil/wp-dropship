<?php
global $wpdb;
return [
    'tables' => [
        'product' => $wpdb->prefix . 'blds_product_map',
        'category' => $wpdb->prefix . 'blds_category_map',
        'order' => $wpdb->prefix . 'blds_order_map',
    ],

    'options' => [
        'access_token' => 'blds_access_token'
    ],

    'paths' => [
        'base' => realpath(__DIR__ . '/../bigly-dropship.php')
    ],

    'remote' => [
        'base' => 'http://dropship.biglytech.net',
        'sync' => 'api/sync',
        'access_token' => 'oauth/token'
    ],
];
