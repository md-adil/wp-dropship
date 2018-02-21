<?php
namespace Bigly\Dropship\Controllers;

use Bigly\Dropship\Config;
use Bigly\Dropship\Library\Client;

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
        /*$id = $_POST['id'];
            if (!preg_match('/^[0-9]*$/', $id)) {
                echo 'id always in integer';
            }*/
        // define variables and set to empty values
        $usernameErr = $idErr = $secretErr = $passwordErr = "";
        $username = $id = $client_secret = $comment = "";
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (empty($_POST["username"])) {
                $usernameErr = "Username/Email is required";
              } else {
                $username = test_input($_POST["username"]);
                // check if name only contains letters and whitespace
                if (!preg_match("/^[a-zA-Z @ ]*$/",$username)) {
                  $nameErr = "Only letters and white space allowed"; 
                }
            }

            if (empty($_POST["id"])) {
                $idErr = "Client Id is required";
              } else {
                $id = test_input($_POST["id"]);
                // check if name only contains letters and whitespace
                if (!preg_match('/^[0-9]*$/', $id)) {
                  $idErr = "Only user number"; 
                }
            }

            if (empty($_POST["client_secret"])) {
                $secretErr = "Client Secret is required";
              } else {
                $client_secret = test_input($_POST["username"]);
                // check if name only contains letters and whitespace
                if (!preg_match("/(A-Za-z0-9]+/", $client_secret)) {
                  $secretErr = "Only use numbers and letters"; 
                }
            }
                function test_input($data) {
                  $data = trim($data);
                  $data = stripslashes($data);
                  return $data;
                }
            $res = $this->request->post($tokenUrl, [
            'body' => [
                'grant_type' => filter_var('password', FILTER_SANITIZE_STRING),
                'client_id' => filter_var($data['client_id'], FILTER_SANITIZE_STRING),
                'client_secret' => filter_var($data['client_secret'], FILTER_SANITIZE_STRING),
                'username' => stripslashes($data['username']),
                'password' => filter_var($_POST['password'], FILTER_SANITIZE_STRING)
            ]
        ]);
        /*$res = $this->request->post($tokenUrl, [
            'body' => [
                'grant_type' => filter_var('password', FILTER_SANITIZE_STRING),
                'client_id' => filter_var($_POST['client_id'], FILTER_SANITIZE_STRING),
                'client_secret' => filter_var($_POST['client_secret'], FILTER_SANITIZE_STRING),
                'username' => stripslashes($_POST['username']),
                'password' => filter_var($_POST['password'], FILTER_SANITIZE_STRING)
            ]
        ]);*/
        
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
                'error' => 'Invalid json response'
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
}
