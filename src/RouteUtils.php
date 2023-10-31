<?php

namespace Route;

trait RouteUtils
{
    public static $handleRoute;

    public static function prefix($name)
    {
        self::$handleRoute->currentGroup = $name;
        return self::$handleRoute;
    }

    public static function listFolderRoutes($path)
    {
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
    }

    public static function add($cb = null)
    {
        require_once $cb;
    }
}
