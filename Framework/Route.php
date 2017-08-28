<?php
namespace Bigly\Dropship\Framework;

class Route
{
    protected static $routers = [];

    protected static function map($method, $pattern, $controller)
    {
        $router = new Router($method, $pattern, $controller);
        static::$routers[] = $router;
        return $router;
    }

    public static function register()
    {
        $routers = static::$routers;

        add_action('admin_menu', function () use ($routers) {
            foreach ($routers as $router) {
                if (strtoupper($router->method) !== strtoupper($_SERVER['REQUEST_METHOD'])) {
                    continue;
                }
                add_menu_page(
                    $router->title,
                    $router->name,
                    'manage_options',
                    $router->path,
                    $router->action,
                    plugin_dir_url(__FILE__) . $router->icon,
                    20
                );
            }
        });
    }

    public static function __callStatic($name, $args)
    {
        return static::map($name, $args[0], $args[1]);
    }
}
