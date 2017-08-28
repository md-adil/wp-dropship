<?php
/**
 * This is supposed to get called automatically when try to uninstall the plugin
 */
// if uninstall.php is not called by WordPress, die

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
$option_name = 'wporg_option';
 
delete_option($option_name);
 
// for site options in Multisite
delete_site_option($option_name);
 
// drop a custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mytable");
