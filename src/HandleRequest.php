<?php

namespace Route;

use Route\Helper;

class HandleRequest
{
    private array $params = [];
    private string $contentType;
    private string $method;
    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->params['query'] = $_GET ?? [];
        $this->handleContentType();
    }

    private function handleContentType()
    {
        $body = file_get_contents('php://input') ?: null;
        if ($this->contentType === "application/x-www-form-urlencoded" || strpos($this->contentType, 'multipart/form-data') !== false) {
            $this->processRequest($body);
        } else if ($this->contentType === 'application/json') {
            if ($body !== null) {
                $this->params['body'] = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Error decoding JSON: ' . json_last_error_msg());
                }
            }
        }
    }

    private function getToken()
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

    public function setParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->params['params'][$key] = $value;
        }
    }

    public function getBody()
    {
        return json_decode(json_encode($this->params['body'] ?? []));
    }

    public function getQuery()
    {
        return json_decode(json_encode($this->params['query'] ?? []));
    }

    public function getParams()
    {
        return json_decode(json_encode($this->params['params'] ?? []));
    }

    public function getFiles()
    {
        return json_decode(json_encode($this->params['files'] ?? []));
    }

    public function getRequest()
    {
        $this->request->body = $this->getBody();
        $this->request->query = $this->getQuery();
        $this->request->params = $this->getParams();
        $this->request->files = $this->getFiles();
        $token = $this->getToken();
        if ($token !== null) {
            $this->request->authorization = $this->getToken();
        }
        return $this->request;
    }
}
