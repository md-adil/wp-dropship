<?php
namespace Bigly\Dropship\Controllers;

class CredentialController extends Controller
{
    protected $credential_prefix = 'biglydropship_credentials';
    public function index()
    {
        global $wpdb;
        $credential = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}options WHERE option_name='{$this->credential_prefix}'");
        list($id, $clientId, $clientSecret) = [null, null, null];
        if ($credential) {
            $credentials = unserialize($credential->option_value);
            $id = $credential->option_id;
            $clientId = $credentials['client_id'];
            $clientSecret = $credentials['secret_key'];
        }

        $this->view('api-credentials.php', compact('id', 'clientId', 'clientSecret'));
    }

    public function store()
    {
        global $wpdb;
        $data = serialize([
            'client_id' => $_POST['client_id'],
            'secret_key' => $_POST['secret_key']
        ]);
        if ($_POST['id']) {
            $wpdb->update(
                $wpdb->prefix . 'options',
                [
                    'option_value' => $data
                ],
                [
                    'option_id' => $_POST['id']
                ]
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'options',
                [
                    'option_name' => $this->credential_prefix,
                    'option_value' => $data
                ]
            );
        }

        wp_send_json([
            'redirect' => 'done'
        ]);
    }

    public function syncProducts()
    {
        wp_remote_get('', [
            
        ]);
    }
}
