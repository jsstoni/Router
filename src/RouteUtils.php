<?php

namespace Route;

use LogicException;

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

    public static function add($cb = null)
    {
        require_once $cb;
    }
}
