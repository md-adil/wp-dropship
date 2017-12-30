<?php

/**
 * Plugin Name: Bigly Dropship
 * Plugin URI: dropship.biglytech.net
 * Description: Sell your product everywhere
 * Version: 0.1
 * Author: Md Adil <md-adil@live.com>
 */

define('BIGLY_DROPSHIP_FILE', __FILE__);

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

function run_biglydropship()
{
    require(__DIR__ . '/routes.php');
}

run_biglydropship();
