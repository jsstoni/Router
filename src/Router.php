<?php

namespace Route;

use Route\RouteUtils;
use Route\HandleRoute;

use LogicException;

abstract class Router extends HandleRoute
{
    use RouteUtils;

    public static function listFolderRoutes($path)
    {
        if (file_exists($path) && is_dir($path)) {
            $files = glob($path . '/*.php');
            foreach ($files as $file) {
                if (basename($file) != "web.php") {
                    self::$handleRoute->currentGroup = '/' . str_replace(".php", "", basename($file));
                    require_once $file;
                } else {
                    self::$handleRoute->currentGroup = "";
                    require_once $file;
                }
            }
        } else {
            throw new LogicException("the `routes` folder does not exist in the project");
        }
    }

    public static function create(string $path, string $base)
    {
        define("BASE_PATH", $path);
        self::$handleRoute = new HandleRoute($path, $base);
        $routesPath = $path . "/routes/";
        self::listFolderRoutes($routesPath);
    }

    public static function __callStatic($name, $arguments)
    {
        self::$handleRoute->{$name}(...$arguments);
    }

    public static function run()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = $_SERVER['REQUEST_URI'];
        self::$handleRoute->dispatch($method, $url);
    }
}
