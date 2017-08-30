<?php

namespace Bigly\Dropship;

/**
*
*/
class Deactivator
{
    public function __construct($path)
    {
        register_deactivation_hook($path, [$this, 'deactivate']);
    }

    public function deactivate()
    {
        $this->dropTables();
    }

    public function dropTables()
    {
        $this->dropTable(Config::get('tables.product'));
        $this->dropTable(Config::get('tables.category'));
    }

    public function dropTable($table)
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}
