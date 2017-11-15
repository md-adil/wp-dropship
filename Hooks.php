<?php
namespace Bigly\Dropship;

use Bigly\Dropship\Controllers\ActivationController;
use Bigly\Dropship\Controllers\OrderController;

/**
*
*/
class Hooks
{
    public function register($path)
    {
        $orderController = new OrderController;
        $activationController = new ActivationController;
        register_activation_hook($path, [ $activationController, 'activate' ]);
        register_deactivation_hook($path, [ $activationController, 'deactivate']);

        // Woocommers
        add_action('woocommerce_new_order', [$orderController, 'placed'], 10, 1);
        add_action('woocommerce_order_status_completed', [$orderController, 'completed'], 10, 1);
        add_action('woocommerce_order_status_failed', [$orderController, 'failed'], 10, 1);
        add_action('woocommerce_order_status_on-hold', [$orderController, 'onHold'], 10, 1);
        add_action('woocommerce_order_status_refunded', [$orderController, 'refunded'], 10, 1);
        add_action('woocommerce_order_status_cancelled', [$orderController, 'cancelled'], 10, 1);
    }
}
