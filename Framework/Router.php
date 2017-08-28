<?php
namespace Bigly\Dropship\Framework;

/**
*
*/
class Router
{
    public $method;
    public $name;
    public $title;
    public $path;
    public $action = '';
    protected $controllerPrefix = '\Bigly\Dropship\Controllers';

    public function __construct($method, $path, $controller)
    {
        $this->method = $method;
        $this->path = $path;
        $this->resolveControllerAction($controller);
    }
    
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    public function title($title)
    {
        $this->title = $title;
        return $this;
    }

    public function icon($icon)
    {
        $this->icon = $icon;
        return $this;
    }
    
    protected function resolveControllerAction($controllerAction)
    {
        list($controller, $action) = explode('@', $controllerAction);
        $this->action = [
            call_user_func([$this->controllerPrefix . '\\' . $controller, 'getInstance']),
            $action
        ];
    }
}
