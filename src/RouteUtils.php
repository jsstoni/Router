<?php

namespace Route;

use LogicException;

trait RouteUtils
{
    public static $handleRoute;

    public static function prefix($name)
    {
        self::$handleRoute->currentGroup = '/' . trim($name, '/');
        return self::$handleRoute;
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

    public static function add($cb = null)
    {
        require_once $cb;
    }
}
