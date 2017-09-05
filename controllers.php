<?php
namespace Bigly\Dropship\Controllers;

function mainMenu()
{
	require(__DIR__ . '/views/home.php');
}

function credentials()
{
    global $wpdb;
    require(__DIR__ . '/views/credentials.php');
}
