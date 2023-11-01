<?php

namespace Route;

use Route\Helper;

class HandleRequest
{
    private $params = array();
    private $contentType;
    private $method;
    private $request;

    public function __construct()
    {
        $this->request = new Request();
        $body = file_get_contents('php://input') ?: null;
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->params['query'] = $_GET ?? [];
        if ($this->contentType === "application/x-www-form-urlencoded" || strpos($this->contentType, 'multipart/form-data') !== false) {
            $this->processRequest($body);
        } else if ($this->contentType === 'application/json') {
            $this->params['body'] = json_decode($body, true);
        }
    }

    public function getToken()
    {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $token = null;
            $authorizationHeader = $headers['Authorization'];
            $bearerPrefix = 'Bearer ';
            if (substr($authorizationHeader, 0, strlen($bearerPrefix)) === $bearerPrefix) {
                $token = substr($authorizationHeader, strlen($bearerPrefix)); // Obtener el token Bearer
            }
            return Helper::decodeToken($token);
        }
    }

    private function processFormData($body): array
    {
        preg_match('/boundary=(.*)$/', $this->contentType, $matches);
        $blocks = preg_split('/-+' . ($matches[1] ?? '') . '/', $body);
        array_pop($blocks);
        $data = [];
        foreach ($blocks as $block) {
            if (empty($block)) {
                continue;
            }
            preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            $data[$matches[1]] = $matches[2];
        }
        return $data;
    }

    public function processRequest($body)
    {
        if ($this->method == 'PUT') {
            parse_str($body, $x_www);
            $this->params['body'] = $this->processFormData($body) ?: $x_www;
        } else {
            $this->params['body'] = $_POST;
            $this->params['files'] = $_FILES;
        }
    }

    public function setParams(array $params, string $k = '')
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $this->params['params'][$key] = $value;
            }
        } else {
            $this->params['params'][$k] = $params;
        }
    }

    public function body()
    {
        return json_decode(json_encode($this->params['body'] ?? []));
    }

    public function query()
    {
        return json_decode(json_encode($this->params['query'] ?? []));
    }

    public function params()
    {
        return json_decode(json_encode($this->params['params'] ?? []));
    }

    public function files()
    {
        return json_decode(json_encode($this->params['files'] ?? []));
    }

    public function getParams()
    {
        $this->request->body = $this->body();
        $this->request->query = $this->query();
        $this->request->params = $this->params();
        $this->request->files = $this->files();
        $this->request->authorization = $this->getToken();
        return $this->request;
    }
}
