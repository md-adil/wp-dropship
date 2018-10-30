<?php
namespace Bigly\Dropship\Controllers;

use Bigly\Dropship\Config;
use Bigly\Dropship\Library\Client;
use WP_Error;

class CredentialController extends Controller
{
    protected $request;

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
        $tokenUrl = $this->config->get('remote.access_token');
        $optionkey = $this->config->get('options.access_token');
        // credientials validation
        $nameErr = $emailErr = $genderErr = $websiteErr = "";
        $client_id = $_POST['client_id'];
        $client_secret = $_POST['client_secret'];
        $username = stripslashes($_POST['username']);
        $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
            if($client_id) {                
                if (!preg_match('/^[0-9]*$/', $client_id)) {
                    return [
                    'status' => 'fail',
                    'message' => 'Client id only contain numbers without any space'
                    ];
                }
            }

            if($client_secret) {
                if (!preg_match('/^[0-9a-zA-Z]+$/', $client_secret)) {
                  return [
                    'status' => 'fail',
                    'message' => 'Client secret not match'
                    ]; 
                }
            }

        $res = $this->request->post($tokenUrl, [
            'body' => [
                'grant_type' => 'password',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'username' => $username,
                'password' => $password
            ]
        ]);
        
        if($res instanceOf \WP_Error) {
            return [
                'status' => 'fail',
                'message' => $res->get_error_messages()
            ];
        }

        $res = json_decode($res['body']);
      
        if ($res === null
            && json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'fail',
                'message' => 'Something went wrong while accessing credentials.',
                'error' => 'Invalid json response',
                'response' => $res['body']
            ];
        }
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

    public function registerWebhook()
    {
        $tokenUrl = $this->config->get('remote.webhook');
        $token = wp_generate_password(60);
        update_option($this->config->get('options.webhook_token'), $token);
        $res = $this->request->withAuth()->post($tokenUrl, [
            'body' => [
                'url' => site_url('?webhook-listener=bigly'),
                'token' => $token,
            ]
        ]);
        
        if($res instanceOf WP_Error) {
            return [
                'status' => 'fail',
                'message' => $res->get_error_messages()
            ];
        }

        return [
            'status' => 'ok',
        ] + $res;
    }
}
