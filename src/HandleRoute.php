<?php

namespace Route;

use Route\HandleRequest;
use Route\Response;
use LogicException;

class HandleRoute
{
    protected array $routes = [];
    protected string $currentGroup;
    protected string $main = '/';
    protected Response $response;

    public function __construct(string $base)
    {
        $this->response = new Response();
        $this->main = $base;
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
                throw new LogicException('Route already exists: ' . $method . ' ' . $path);
            }
        }
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    private function typeHandler(string|array|callable $handler)
    {
        if (is_string($handler)) {
            $handlerParts = explode("@", $handler);
            if (count($handlerParts) !== 2) {
                throw new LogicException("Invalid handler format.");
            }
            [$className, $methodName] = $handlerParts;
            $controller = [new $className, $methodName];
        } else if (is_array($handler) && count($handler) === 2) {
            $controller = $handler;
        } else if (is_callable($handler)) {
            $controller = $handler;
        } else {
            throw new LogicException("Invalid handler format.");
        }

        return $controller;
    }

    public function dispatch(string $method, string $url)
    {
        $urlParts = parse_url($url);
        $pathWithQuery = $urlParts['path'] . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');
        if ($method === 'OPTIONS') { //preflight fix
            $this->response->status(200);
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
                    $request = $handleRequest->getRequest();
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
                    return call_user_func($this->typeHandler($route['handler']), $request, $this->response);
                }
            }
        }
        $this->response->status(404)->send("404 Not Found");
    }
}
