<?php

namespace Route;

use League\Plates\Engine as Engine;
use League\Plates\Extension\Asset as Asset;
use LogicException;

class Response
{
    private Engine $tplEngie;
    private string $pathToView;

    public function __construct()
    {
        $this->pathToView = BASE_PATH;
        $pathViews = $this->pathToView . "/resources/views";
        if (is_dir($pathViews)) {
            $this->tplEngie = new Engine($pathViews);
            $this->tplEngie->loadExtension(new Asset($this->pathToView . "/public/", false));
            $this->tplEngie->addFolder("layouts", $pathViews . "/layouts");
        }
    }

    public function status(int $statusCode): self
    {
        http_response_code($statusCode);
        return $this;
    }

    public function json(array $obj = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($obj);
    }

    public function send(string $any = ""): void
    {
        echo $any;
    }

    public function render(string $path, array $data = [])
    {
        if (is_dir($this->pathToView)) {
            try {
                echo $this->tplEngie->render($path, $data);
            } catch (\Exception $error) {
                throw new LogicException("file '$path' not found in view");
            }
        } else {
            throw new LogicException("path for view application does not exist");
        }
    }
}
