<?php

namespace Route;

use Route\HandleRequest;
use Route\Response;

class HandleRoute
{
    protected $routes = [];
    public $currentGroup = '';
    public $main = '/';
    public $path_main;

    public function __construct(string $dir, $base)
    {
        $this->main = $base;
        $this->path_main = $dir;
    }

    public function group(callable $cb): void
    {
        if (is_callable($cb)) {
            $cb();
        }
        $this->currentGroup = '';
    }

    public function addRoute(string $method, string $path, $handler, $middleware): void
    {
        $path = $this->currentGroup != '' ? ($path != '/' ? $this->currentGroup . $path : $this->currentGroup) : $path;
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                throw new \Exception('Route already exists: ' . $method . ' ' . $path);
            }
        }
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    private function typeHandler($handler)
    {
        if (is_string($handler)) {
            $handlerParts = explode("@", $handler);
            if (count($handlerParts) !== 2) {
                throw new \Exception("Invalid handler format.");
            }
            [$className, $methodName] = $handlerParts;
            $controller = [new $className, $methodName];
        } else if (is_array($handler) && count($handler) === 2) {
            $controller = $handler;
        } else if (is_callable($handler)) {
            $controller = $handler;
        } else {
            throw new \Exception("Invalid handler format.");
        }

        return $controller;
    }

    public function dispatch(string $method, string $url)
    {
        $response = new Response();
        $urlParts = parse_url($url);
        $pathWithQuery = $urlParts['path'] . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');
        if ($method === 'OPTIONS') { //preflight fix
            $response->status(200);
            exit();
        }
        foreach ($this->routes as $route) {
            if ($route['method'] == $method) {
                $main = $this->main === '/' ? trim($this->main, '/') : $this->main;
                $path = rtrim($main . $route['path'], '/');
                $pattern = '#^' . preg_replace('#/:([^/]+)#', '/(?<$1>[^/]+)', $path) . '(/?)?(\?.*)?$#';
                if (preg_match($pattern, $pathWithQuery, $matches)) {
                    $handleRequest = new HandleRequest();
                    $params = array_intersect_key($matches, array_flip(array_filter(array_keys($matches), 'is_string')));
                    $handleRequest->setParams($params);
                    $request = $handleRequest->getParams();
                    foreach ($route['middleware'] as $middleware) {
                        $midlePart = explode(":", $middleware);
                        $className = $midlePart[0];
                        $methodName = $midlePart[1];
                        $classMiddleware = "Middleware\\{$className}";
                        if (class_exists($classMiddleware)) {
                            $middleInstance = new $classMiddleware();
                            $call = call_user_func(array($middleInstance, $methodName), $request);
                            if (isset($call['error'])) {
                                exit(json_encode($call));
                            }
                        }
                    }
                    return call_user_func($this->typeHandler($route['handler']), $request, $response);
                }
            }
        }
        $response->status(404);
        exit("404 Not Found");
    }
}
