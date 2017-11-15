<?php
/**
 * Plugin Name: Bigly Dropship
 * Plugin URI: dropship.biglytech.net
 * Description: Sell your product everywhere
 * Version: 0.1
 * Author: Md Adil <md-adil@live.com>
 */

use Bigly\Dropship\Framework\App;
use Bigly\Dropship\Framework\Container;
use Bigly\Dropship\Config;
use Bigly\Dropship\Activator;
use Bigly\Dropship\Deactivator;
use Bigly\Dropship\RegisterOrderHook;

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
// Includes
require(__DIR__ . '/includes/functions.php');
require(__DIR__ . '/actions.php');
require(__DIR__ . '/routes.php');

require(__DIR__ . '/process/activate.php');
require(__DIR__ . '/process/deactivate.php');



function run_biglydropship()
{
    global $wpdb;
    $config = require(__DIR__ . '/configs/config.php');
    require(__DIR__ . '/hooks.php');

    $hook = new Hooks(__FILE__);
    $hook->register();
    
    if (file_exists(__DIR__ . '/configs/config.local.php')) {
        $config = array_replace_recursive($config, require(__DIR__ . '/configs/config.local.php'));
    }

    Config::set($config);
    $activator = new Activator(__FILE__);
    $deactivator = new Deactivator(__FILE__);
    new RegisterOrderHook();
    // Register Hooks
}

run_biglydropship();
