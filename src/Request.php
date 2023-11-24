<?php

namespace Route;

final class Request
{
    public array | object $body;
    public array | object $query;
    public array | object $params;
    public array | object $files;
    public ?string $authorization;
}
