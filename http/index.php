<?php

use \App\Lib\Router;

// Autoload 自动载入
require './vendor/autoload.php';

$http = new Swoole\Http\Server("0.0.0.0", 8080);

$http->on('request', function ($request, $response) {
    $response->header("Content-Type", "text/html; charset=utf-8");
    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        $response->end();
        return;
    }
    // 控制输出
    ob_start();
    Router::dispatch($request, $response);
    $result = ob_get_contents();
    ob_end_clean();
    $response->end($result);
});

$http->start();