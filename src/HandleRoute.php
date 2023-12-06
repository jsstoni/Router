<?php

namespace Route;

use Route\{HandleRequest, Response};
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

    private function typeHandler(string | array | callable $handler)
    {
        if (is_string($handler) || (is_array($handler) && count($handler) === 2)) {
            $handlerParts = is_string($handler) ? explode("@", $handler) : $handler;
            if (count($handlerParts) !== 2) {
                throw new LogicException("Invalid handler format.");
            }
            [$className, $methodName] = $handlerParts;
            $controller = [new $className, $methodName];
        } else if (is_callable($handler)) {
            $controller = $handler;
        } else {
            throw new LogicException("Invalid handler format.");
        }

        return $controller;
    }

    private function applyMiddleware(string $middleware, $request)
    {
        list($className, $methodName) = explode(":", $middleware);
        $classMiddleware = "Middleware\\{$className}";

        if (class_exists($classMiddleware)) {
            $middlewareInstance = new $classMiddleware();
            $result = $middlewareInstance->{$methodName}($request);

            if (isset($result['error'])) {
                throw new \LogicException(json_encode($result));
            }
        }
    }

    private function processRoutes(string $method, $pathWithQuery)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] == $method) {
                $main = $this->main === '/' ? trim($this->main, '/') : $this->main;
                $path = rtrim($main . $route['path'], '/');
                $pattern = '#^' . preg_replace('#/:([^/]+)#', '/(?<$1>[^/]+)', $path) . '(/?)?(\?.*)?$#';
                if (preg_match($pattern, $pathWithQuery, $matches)) {
                    $this->handleRequest($route, $matches);
                    return;
                }
            }
        }
        throw new \LogicException("Router not found");
    }

    private function handleRequest(array $route, array $matches)
    {
        $handleRequest = new HandleRequest();
        $params = array_intersect_key($matches, array_flip(array_filter(array_keys($matches), 'is_string')));
        $handleRequest->setParams($params);
        $request = $handleRequest->getRequest();
        foreach ($route['middleware'] as $middleware) {
            $this->applyMiddleware($middleware, $request);
        }
        $this->executeHandler($route['handler'], $request);
    }

    private function executeHandler($handler, $request)
    {
        $handlerFunction = $this->typeHandler($handler);
        return $handlerFunction($request, $this->response);
    }

    public function dispatch(string $method, string $url)
    {
        $urlParts = parse_url($url);
        $pathWithQuery = $urlParts['path'] . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');
        if ($method === 'OPTIONS') { //preflight fix
            return $this->response->status(200);
        }
        try {
            $this->processRoutes($method, $pathWithQuery);
        } catch (\LogicException $error) {
            $this->response->status(404)->send("404 Not Found");
        }
    }
}
