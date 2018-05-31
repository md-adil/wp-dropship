<?php

/**
 * Plugin Name: Bigly Dropship
 * Plugin URI: dropship.biglytech.net
 * Description: Sell your product everywhere
 * Version: 1.0
 * Author: Bigly Technologies PVT. Limited<info@biglytech.net>
 * Author URI github.com/md-adil
 *
 *
 * @package Bigly Dropship
 * @category Ecommerce
 * @author Md Adil <md-adil@live.com>
 * @version 1.0
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


add_filter('image_downsize', function($f, $id, $size) {
    $sizes = [
        'thumbnail' => [ 'thumb', 150, 150 ],   // Thumbnail (150 x 150 hard cropped)
        'medium' => [ 'medium', 300, 300 ],    // Medium resolution (300 x 300 max height 300px)
        'large' => [ 'large', 1024, 1024 ],   // Large resolution (1024 x 1024 max height 1024px)
    ];
    if(!$id) return;
    $post = get_post($id);
    if(!$post) return;
    if(!$post->post_content === 'biglydropship') return;
    if(is_array($size)) {
        array_unshift($size, 'large');
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
    require(__DIR__ . '/routes.php');
}

run_biglydropship();
