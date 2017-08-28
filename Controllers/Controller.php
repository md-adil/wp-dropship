<?php

namespace Bigly\Dropship\Controllers;

class Controller
{
    protected static $instances = [];
    protected function view($path)
    {
        require_once(__DIR__ . '/../views/' . $path);
    }

    public function redirect($path)
    {
        header("Location: " . $path);
        die();
    }

    public function redirectBack()
    {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        die();
    }

    public static function getInstance()
    {
        $className = get_called_class();
        if (!isset(static::$instances[$className])) {
            static::$instances[$className] = new static;
        }

        return static::$instances[$className];
    }
}
