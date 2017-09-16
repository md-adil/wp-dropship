<?php
namespace Bigly\Dropship\Controllers;

use Bigly\Dropship\Config;

function mainMenu()
{
	$tokenKey = Config::get('options.access_token');
	if(!get_option($tokenKey)) {
		header('LOCATION: admin.php?page=bigly-dropship/credentials');
		die();
	}
	require(__DIR__ . '/views/home.php');
}

function credentials()
{
    global $wpdb;
    require(__DIR__ . '/views/credentials.php');
}
