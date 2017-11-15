<?php

use Bigly\Dropship\Controllers\CredentialController;
use Bigly\Dropship\Controllers\HomeController;
use Bigly\Dropship\Controllers\SyncController;

add_action('admin_menu', function () {
    add_menu_page(
        'Bigly Dropship',
        'Bigly Dropship',
        'manage_options',
        'bigly-dropship',
        [ new HomeController, 'index'],
        plugin_dir_url(__FILE__) . 'settings',
        20
    );

    add_submenu_page(
        'bigly-dropship',
        'Credentials',
        'Credentials',
        'manage_options',
        'bigly-dropship/credentials',
        [ new CredentialController, 'index' ]
    );
});

add_action('wp_ajax_blds_access-token', [ new CredentialController, 'getAccessToken' ]);
add_action('wp_ajax_blds_sync', [ new SyncController, 'sync' ]);
