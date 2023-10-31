<?php

use Route\Request;
use Route\Response;
use Route\Router;

Router::get('/', function(Request $req, Response $res) {
    $res->status(200)->send("test message");
});