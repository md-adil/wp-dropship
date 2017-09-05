<?php
namespace Bigly\Dropship\Framework;

class Route
{
    protected static $routers = [];
    const ROUTE_PREFIX = 'blds';
    const CONTROLLER_PREFIX = '\Bigly\Dropship\Controllers';

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

    public static function ajax($pattern, $controller)
    {
        $action = static::resolveControllerAction($controller);
        add_action('wp_ajax_' . self::ROUTE_PREFIX . '_' . $pattern, function() use($action) {
            $res = call_user_func($action);
            if($res !== null) {
                wp_send_json($res);
            }
        });
    }

    protected static function resolveControllerAction($controllerAction)
    {
        list($controller, $action) = explode('@', $controllerAction);
        return [
            call_user_func([static::CONTROLLER_PREFIX . '\\' . $controller, 'getInstance']),
            $action
        ];
    }

    public static function __callStatic($name, $args)
    {
        return static::map($name, $args[0], $args[1]);
    }
}
