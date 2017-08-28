<?php
/**
 * Plugin Name: Bigly Dropship
 * Plugin URI: dropship.biglytech.net
 * Description: Sell your product everywhere
 * Version: 0.1
 * Author: Md Adil <md-adil@live.com>
 */

spl_autoload_register(function ($class) {
    // project-specific namespace prefix
    $prefix = 'Bigly\\Dropship\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);
    
    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

require(__DIR__ . '/routes.php');

register_activation_hook(__FILE__, 'bigly_dropship_activating');
register_deactivation_hook(__FILE__, 'bigly_dropship_deactivating');
register_uninstall_hook(__FILE__, 'bigly_dropship_uninstall');

// add_action('init', function () {
//     add_menu_page('Bigly Dropship Configuration', 'Bigly');
// });
// require_once(__DIR__ . '/controllers/CredentialController.php');

// function biglydropship_manage_credentials()
// {
//     $controller = new CredentialController();
//     $controller->index();
// }

// function biglydropship_addmenu()
// {
//     $controller = new CredentialController();
//     add_menu_page(
//         'Bigly Dropship',
//         'Bigly Dropship',
//         'manage_options',
//         'bigly-dropship-credentials',
//         [$controller, 'index'],
//         plugin_dir_url(__FILE__) . 'images/icon_wporg.png',
//         20
//     );
// }
// add_action('admin_menu', 'biglydropship_addmenu');
