<?php
namespace Bigly\Dropship\Controllers;

use Bigly\Dropship\Config;

class CredentialController extends Controller
{
    protected $credential_prefix = 'biglydropship_credentials';
    public function index()
    {
        
    }

    public function getAccessToken()
    {
        $optionkey = Config::get('options.access_token');
        $tokenUrl = Config::get('remote.base') . '/' . Config::get('remote.access_token');
        $res = wp_remote_post($tokenUrl, [
            'body' => [
                'grant_type' => 'password',
                'client_id' => $_POST['client_id'],
                'client_secret' => $_POST['client_secret'],
                'username' => $_POST['username'],
                'password' => $_POST['password']
            ]
        ]);
        $res = json_decode($res['body']);
        if($res->error) {
            wp_send_json([
                'status' => 'fail',
                'message' => $res->message,
                'data' => $_POST
            ]);
        } else {
            $token = $res->access_token;
            if($token) {
                update_option($optionkey, $token);
                wp_send_json([
                    'status' => 'ok',
                    'message' => 'Status has been updated.'
                ]);
            } else {
                wp_send_json([
                    'status' => 'fail',
                    'Invalid response found, please contact service provider'
                ]);
            }
        }
    }
}
