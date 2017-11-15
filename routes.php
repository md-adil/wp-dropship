<?php

add_action('admin_menu', function () {
    add_menu_page(
        'Bigly Dropship',
        'Bigly Dropship',
        'manage_options',
        'bigly-dropship',
        'Bigly\Dropship\Controllers\mainMenu',
        plugin_dir_url(__FILE__) . 'settings',
        20
    );

    add_submenu_page(
        'bigly-dropship',
        'Credentials',
        'Credentials',
        'manage_options',
        'bigly-dropship/credentials',
        'Bigly\Dropship\Controllers\credentials'
    );
});
