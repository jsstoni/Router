<?php

namespace Route;

use Route\RouteUtils;
use Route\HandleRoute;

abstract class Router extends HandleRoute
{
    use RouteUtils;

    public static function create(string $path, string $base)
    {
        define("BASE_PATH", $path);
        self::$handleRoute = new HandleRoute($path, $base);
        $routesPath = $path . "/routes/";
        self::listFolderRoutes($routesPath);
    }

    public static function get(String $path, $handler, ...$middleware)
    {
        self::$handleRoute->addRoute('GET', $path, $handler, $middleware);
    }

    public static function post(String $path, $handler, ...$middleware)
    {
        self::$handleRoute->addRoute('POST', $path, $handler, $middleware);
    }

    public static function patch(String $path, $handler, ...$middleware)
    {
        self::$handleRoute->addRoute('PATCH', $path, $handler, $middleware);
    }

    public static function put(String $path, $handler, ...$middleware)
    {
        self::$handleRoute->addRoute('PUT', $path, $handler, $middleware);
    }

    public static function delete(String $path, $handler, ...$middleware)
    {
        self::$handleRoute->addRoute('DELETE', $path, $handler, $middleware);
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
