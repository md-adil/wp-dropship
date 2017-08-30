<?php
use Bigly\Dropship\Framework\App;
use Bigly\Dropship\Framework\Container;
use Bigly\Dropship\Config;
use Bigly\Dropship\Activator;
use Bigly\Dropship\Deactivator;

/**
 * Plugin Name: Bigly Dropship
 * Plugin URI: dropship.biglytech.net
 * Description: Sell your product everywhere
 * Version: 0.1
 * Author: Md Adil <md-adil@live.com>
 */

spl_autoload_register(function ($class) {
    $prefix = 'Bigly\\Dropship\\';
    $base_dir = __DIR__ . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

require(__DIR__ . '/actions.php');
require(__DIR__ . '/routes.php');

require(__DIR__ . '/process/activate.php');
require(__DIR__ . '/process/deactivate.php');

function run_biglydropship()
{
    global $wpdb;
    $remoteBaseUrl = 'http://dropship.dev';
    $configs = [
        'tables' => [
            'product' => $wpdb->prefix . 'bds_product_map',
            'category' => $wpdb->prefix . 'bds_category_map'
        ],

        'prefix' => [
        ],

        'paths' => [
            'base' => __FILE__
        ],

        'remote' => [
            'sync' => $remoteBaseUrl . '/api/sync',
            'authorize' => $remoteBaseUrl . '/oauth/authorize',
            'access_token' => $remoteBaseUrl . '/oauth/tokens'
        ],
    ];


    Config::set($configs);

    $activator = new Activator(__FILE__);
    $deactivator = new Deactivator(__FILE__);
}

run_biglydropship();
