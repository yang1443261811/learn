<?php

use \App\Lib\Router;

// Autoload 自动载入
require './vendor/autoload.php';

//$http = new Swoole\Http\Server("0.0.0.0", 8080);
(new \Controllers\IndexController)->index(123);
die;
$http->on('request', function ($request, $response) {
    $response->header("Content-Type", "text/html; charset=utf-8");

    Router::dispatch($request, $response);
//    $response->end("<h1>Hello Swoole. #" . rand(1000, 9999) . "</h1>");
});

$http->start();