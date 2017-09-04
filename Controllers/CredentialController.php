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
            wp_send_json($res->error);
        } else {

        }
    }

    public function storeToken() {
        
    }

    public function syncProducts()
    {
        wp_remote_get('', [
            
        ]);
    }
}
