<?php

namespace Bigly\Dropship;

/**
*
*/
class Activator
{
    private $prefix;
    public function __construct($path)
    {
        register_activation_hook($path, [$this, 'activate']);
        $this->prefix = '';
    }

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
}
