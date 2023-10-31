<?php

require __DIR__ . '/../vendor/autoload.php';

use Route\Router;

Router::create(dirname(__DIR__) . '/examples', '/');
Router::run();
