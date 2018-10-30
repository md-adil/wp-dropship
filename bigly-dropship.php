<?php

/**
 * Plugin Name: Bigly
 * Plugin URI: https://bigly.io
 * Description: Sell your product everywhere
 * Version: 1.1.4
 * Author: Bigly Technologies PVT. Limited<info@biglytech.net>
 * Author URI github.com/md-adil
 *
 *
 * @package Bigly
 * @category Ecommerce
 * @author Md Adil <md-adil@live.com>
 * @version 1.1
 */

define('BIGLY_DROPSHIP_FILE', __FILE__);


add_action('init', 'bigly_register_webhook_listener');
function bigly_register_webhook_listener() {
    if(isset($_GET['webhook-listener']) && $_GET['webhook-listener'] == 'bigly') {
        require(__DIR__ . '/webhook.php');
    }
}

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

add_filter('image_downsize', function($f, $id, $size) {
    $sizes = [
        'thumbnail' => [ 'thumb', 150, 150 ],   // Thumbnail (150 x 150 hard cropped)
        'small' => [ 'small', 472, 472 ],   // Thumbnail (150 x 150 hard cropped)
        'medium' => [ 'medium', 300, 300 ],    // Medium resolution (300 x 300 max height 300px)
        'large' => [ 'large', 1024, 1024 ],   // Large resolution (1024 x 1024 max height 1024px)
    ];

    $is_intermediate = false;
    
    if(!$id) return;
    $post = get_post($id);
    if(!$post) return;
    if(!$post->post_content === 'biglydropship') return;
    if(is_array($size)) {
        $_width = $size[0];
        if($_width <= 280) {
            array_unshift($size, 'thumb');
        } else if($_width <= 472) {
            array_unshift($size, 'small');
        } else if($_width <= 800) {
            array_unshift($size, 'medium');
        } else {
            array_unshift($size, 'large');
        }
    } else if(isset($sizes[$size])) {
        $size = $sizes[$size];
    } else {
        $size = $sizes['large'];
    }

    return [
        str_replace("/large/", "/{$size[0]}/", $post->guid),
        $size[1],
        $size[2],
        $is_intermediate
    ];
}, 10, 3);

// Initialize
function run_biglydropship()
{
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'blds_add_action_links');
    require(__DIR__ . '/routes.php');
}


function blds_add_action_links($links) {
   $links[] = '<a href="https://bigly.io" target="_blank">Website</a>';
   $links[] = '<a href="https://app.bigly.io/login?type=seller" target="_blank">Signup</a>';
   return $links;
}

run_biglydropship();
