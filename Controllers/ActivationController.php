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
        global $wpdb;
        $wpdb->query($this->createProductMapTable());
        $wpdb->query($this->createCategoryMapTable());
    }

    private function createProductMapTable()
    {
        $table = Config::get('tables.product');

        return "CREATE TABLE {$table} (
            post_id BIGINT UNSIGNED,
            product_id INT UNSIGNED
        )";
    }

    private function createCategoryMapTable()
    {
        $table = Config::get('tables.category');
        return "CREATE TABLE {$table} (
            term_id BIGINT UNSIGNED,
            category_id INT UNSIGNED
        )";
    }

    private function createOrderMapTable()
    {
        $table = Config::get('tables.order');
        return "CREATE TABLE {$table} (
            post_id BIGINT UNSIGNED,
            order_id INT UNSIGNED
        )";
    }

    public function deactivate()
    {
        $this->dropTables();
    }

    public function dropTables()
    {
        $this->dropTable(Config::get('tables.product'));
        $this->dropTable(Config::get('tables.category'));
        $this->dropTable(Config::get('tables.order'));
    }

    public function dropTable($table)
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}
