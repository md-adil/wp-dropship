<?php

namespace Bigly\Dropship\Framework;

/**
*
*/
class App
{
    protected $container;
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->registerControllers();
    }
    
    public function getContainer()
    {
        return $this->container;
    }

    public function run()
    {
        $this->registerInitialHooks();
    }

    protected function registerInitialHooks()
    {
        $rootPath = $this->container['settings']['root_path'];
    }

    public function registerControllers()
    {
        $controllers = [
            'ActivationController',
            'CredentialController'
        ];
        
        foreach ($controllers as $controller) {
            $this->container[$controller] = function ($c) use ($controller) {
                $className = '\Bigly\Dropship\Controllers\\' . $controller;
                return new $className($c);
            };
        }
    }
}
