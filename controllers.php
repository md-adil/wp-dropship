<?php
namespace Bigly\Dropship\Controllers;

function mainMenu()
{
}

function credentials()
{
    global $wpdb;
    $credential_prefix = 'biglydropship_credentials';

    $credential = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}options WHERE option_name='{$credential_prefix}'");
    list($id, $clientId, $clientSecret) = [null, null, null];
    if ($credential) {
        $credentials = unserialize($credential->option_value);
        $id = $credential->option_id;
        $clientId = $credentials['client_id'];
        $clientSecret = $credentials['secret_key'];
    }
    require(__DIR__ . '/includes/credentials.php');
}
