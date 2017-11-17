<?php
namespace Bigly\Dropship\Controllers;

use Bigly\Dropship\Config;
use Bigly\Dropship\Library\Client;

class CredentialController extends Controller
{
    protected $request;
    protected $credential_prefix = 'biglydropship_credentials';

    public function __construct()
    {
        parent::__construct();

        $this->request = new Client($this->config);
    }
    
    public function index()
    {
        $this->view('credentials.php');
    }

    public function getAccessToken()
    {
        $optionkey = $this->config->get('options.access_token');
        $tokenUrl = $this->config->get('remote.base') . '/' . $this->config->get('remote.access_token');
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
        if (isset($res->error)) {
            return [
                'status' => 'fail',
                'message' => $res->message,
                'data' => $_POST
            ];
        } else {
            $token = $res->access_token;
            if ($token) {
                update_option($optionkey, $token);
                return [
                    'status' => 'ok',
                    'message' => 'Status has been updated.'
                ];
            } else {
                return [
                    'status' => 'fail',
                    'Invalid response found, please contact service provider'
                ];
            }
        }
    }
}
