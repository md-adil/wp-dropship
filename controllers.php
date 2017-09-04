<?php
namespace Bigly\Dropship\Controllers;

function mainMenu()
{
}

function credentials()
{
    global $wpdb;
    require(__DIR__ . '/views/credentials.php');
}
