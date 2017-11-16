<?php

use Bigly\Dropship\Controllers\Controller;

add_action('admin_menu', function () {
    add_menu_page(
        'Bigly Dropship',
        'Bigly Dropship',
        'manage_options',
        'bigly-dropship',
        Controller::resolve('HomeController@index'),
        plugin_dir_url(__FILE__) . 'settings',
        20
    );

    add_submenu_page(
        'bigly-dropship',
        'Credentials',
        'Credentials',
        'manage_options',
        'bigly-dropship/credentials',
        Controller::resolve('CredentialController@index')
    );
});

add_action('wp_ajax_blds_access-token', Controller::resolve('CredentialController@getAccessToken'));
add_action('wp_ajax_blds_sync', Controller::resolve('SyncController@sync'));
