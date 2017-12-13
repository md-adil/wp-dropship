<?php

namespace Bigly\Dropship\Controllers;

use Bigly\Dropship\Library\Config;

class Controller
{
    protected static $instances = [];
    protected static $isExceptionHandled = false;
    protected static $configInstance;
    protected $config;
    protected $db;
    
    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        if (!static::$isExceptionHandled) {
            set_error_handler([$this, 'handleError']);
            set_exception_handler([$this, 'handleException']);
            static::$isExceptionHandled = true;
        }
        $this->setConfig();
    }
    public function dd() {
        echo '<pre>';
        foreach(func_get_args() as $arg) {
            print_r($arg);
        }
        echo '</pre>';
        die();
    }


    protected function setConfig()
    {
        if (!static::$configInstance) {
            $configs = require(__DIR__ . '/../configs/config.php');
            if (file_exists(__DIR__ . '/../configs/config.local.php')) {
                $configs = array_replace_recursive($configs, require(__DIR__ . '/../configs/config.local.php'));
            }
            static::$configInstance = new Config($configs);
        }
        $this->config = static::$configInstance;
    }
    
    public function handleError($code, $message, $file, $line)
    {
        http_response_code(500);
        wp_send_json(compact('code', 'message', 'file', 'line'));
        exit();
    }

    public function handleException($e)
    {
        $this->handleError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
    }

    protected function view($path, $args = [])
    {
        extract($args);
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

    public function ifset(&$data, $default = null)
    {
        if (isset($data)) {
            return $data;
        }
        return $default;
    }

    public static function resolve($callable)
    {
        return function () use ($callable) {
            list($class, $method) = explode('@', $callable);
            $className = __NAMESPACE__ . '\\' . $class;
            if (!isset(static::$instances[$className])) {
                static::$instances[$className] = new $className;
            }
            $response = call_user_func_array([static::$instances[$className], $method], func_get_args());
            if (is_array($response)) {
                echo wp_send_json($response);
            } elseif ($response) {
                echo $response;
            }
        };
    }
}
