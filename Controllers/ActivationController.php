<?php
namespace Bigly\Dropship\Controllers;

/**
*
*/
class ActivationController extends Controller
{
    public function activate()
    {
        if ( version_compare(PHP_VERSION, '5.4', '<')) {
            die('PHP 5.4 is required.');
        }

        if (!is_plugin_active( 'woocommerce/woocommerce.php' )) {
            die("This plugin requires WooCommerce plugins to be active!");
        }

        $this->createTables();
    }

    public function createTables()
    {
        try {
            $this->db->query($this->createSyncMapTable());
        } catch(\Exception $e) {
            die( $e->getMessage() );
        }
    }

    private function createSyncMapTable()
    {
        $table = $this->config->get('tables.sync');
        return "CREATE TABLE IF NOT EXISTS {$table} (
            `host_id` BIGINT UNSIGNED,
            `guest_id` INT UNSIGNED,
            `type` VARCHAR(10),
            PRIMARY KEY (`host_id`, `guest_id`, `type`)
        )";
    }

    public function deactivate()
    {
        delete_option($this->config->get('options.access_token'));
        delete_option($this->config->get('options.webhook_token'));
    }
}
