<?php

namespace Route;

final class Request
{
    public array $body;
    public array $query;
    public array $params;
    public array $files;
    public ?string $authorization;
}
