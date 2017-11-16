<?php
namespace Bigly\Dropship;

use Bigly\Dropship\Controllers\Controller;

/**
*
*/
class Hooks
{
    public function register($path)
    {
        register_activation_hook($path, Controller::resolve('ActivationController@activate'));
        register_deactivation_hook($path, Controller::resolve('ActivationController@deactivate'));

        // Woocommers
        add_action('woocommerce_new_order', Controller::resolve('OrderController@placed'), 10, 1);
        add_action('woocommerce_order_status_completed', Controller::resolve('OrderController@completed'), 10, 1);
        add_action('woocommerce_order_status_failed', Controller::resolve('OrderController@failed'), 10, 1);
        add_action('woocommerce_order_status_on-hold', Controller::resolve('OrderController@onHold'), 10, 1);
        add_action('woocommerce_order_status_refunded', Controller::resolve('OrderController@refunded'), 10, 1);
        add_action('woocommerce_order_status_cancelled', Controller::resolve('OrderController@cancelled'), 10, 1);
    }
}
