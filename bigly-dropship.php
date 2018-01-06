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

$sizes = [
    'thumbnail' => [ 150, 150 ],   // Thumbnail (150 x 150 hard cropped)
    'medium' => [ 300, 300 ],    // Medium resolution (300 x 300 max height 300px)
    'large' => [ 1024, 1024 ],   // Large resolution (1024 x 1024 max height 1024px)
     
];

$count = 0;
add_filter('image_downsize', function($f, $id, $size) use( $sizes ) {
    global $count;
    $width = 1024; $height = 1024;
    $is_intermediate = false;
    if(!$id) return;
    $count ++;
    // echo 'count-id:' . $id . '-' . $count;
    $post = get_post($id);
    if(!$post) return;
    if(!$post->post_content === 'biglydropship') return;
    return [
        $post->guid,
        $width,
        $height,
        $is_intermediate
    ];

}, 10, 3);

// Initialize

function run_biglydropship()
{
    require(__DIR__ . '/routes.php');
}

run_biglydropship();
