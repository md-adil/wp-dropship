<?php
namespace Bigly\Dropship\Controllers;

/**
*
*/
class ActivationController extends Controller
{
    public function activate()
    {
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
        return "CREATE TABLE {$table} (
            `host_id` BIGINT UNSIGNED,
            `guest_id` INT UNSIGNED,
            `type` VARCHAR(10),
            PRIMARY KEY (`host_id`, `guest_id`, `type`)
        )";
    }

    public function deactivate()
    {
        $this->dropTables();
    }

    public function dropTables()
    {
        $this->dropTable($this->config->get('tables.sync'));
    }

    public function dropTable($table)
    {
        $this->db->query("DROP TABLE IF EXISTS {$table}");
    }
}
