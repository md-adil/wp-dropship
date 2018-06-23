<?php 

use Bigly\Dropship\Controllers\Controller;

// Adding menus
add_action('admin_menu', function () {
    add_menu_page(
        'Bigly Dropship',
        'Bigly Dropship',
        'manage_options',
        'bigly-dropship',
        Controller::resolve('CredentialController@index'),
        plugin_dir_url(__FILE__) . 'settings',
        20
    );

    // add_submenu_page(
    //     'bigly-dropship',
    //     'Credentials',
    //     'Credentials',
    //     'manage_options',
    //     'bigly-dropship/credentials',
    //     Controller::resolve('CredentialController@index')
    // );
});

// Ajax routes
add_action('wp_ajax_blds_access-token', Controller::resolve('CredentialController@getAccessToken'));
add_action('wp_ajax_blds_sync', Controller::resolve('SyncController@sync'));
add_action('wp_ajax_blds_register-webhook', Controller::resolve('CredentialController@registerWebhook'));


// Hooks

// Activation Hooks
register_activation_hook(BIGLY_DROPSHIP_FILE, Controller::resolve('ActivationController@activate'));
register_deactivation_hook(BIGLY_DROPSHIP_FILE, Controller::resolve('ActivationController@deactivate'));

// Woocommers Hooks
// add_action('woocommerce_order_status_on-hold', Controller::resolve('OrderController@onHold'), 10, 1);
add_action('woocommerce_thankyou', Controller::resolve('OrderController@placed'), 10, 1);
add_action('woocommerce_order_status_processing', Controller::resolve('OrderController@processing'), 10, 1);
add_action('woocommerce_order_status_completed', Controller::resolve('OrderController@completed'), 10, 1);
add_action('woocommerce_order_status_failed', Controller::resolve('OrderController@failed'), 10, 1);
add_action('woocommerce_order_status_refunded', Controller::resolve('OrderController@refunded'), 10, 1);
add_action('woocommerce_order_status_cancelled', Controller::resolve('OrderController@cancelled'), 10, 1);
