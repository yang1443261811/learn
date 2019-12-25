<?php


//连接重用
//创建资源流的上下文
$context = stream_context_create([
    'socket' => [
        'backlog' => 2000
    ]]);
stream_context_set_option($context, 'socket', 'so_reuseaddr', 1); //设置连接重用
//sock_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1); //复用还处于 TIME_WAIT
$socket = stream_socket_server("tcp://0.0.0.0:8090", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
stream_set_blocking($socket, false);//非阻塞
//绑定事件
$base = new EventBase();
//监听服务端的socket
$event = new  Event($base, $socket, Event::PERSIST | Event::READ | Event::WRITE, function ($socket) use (&$base) {

    $client = stream_socket_accept($socket);

    //global $base;

    //var_dump($socket,$client);

    $base = new EventBase();
    //监听客户端socket
    $event = new  Event($base, $client, Event::PERSIST | Event::READ | Event::WRITE, function ($client) {
        $msg = fread($client, 65535);
//
//         if($msg){ //匹配请求头包含了keep-alive
//
//         }

        $content = '21335435';
        $string = "HTTP/1.1 200 OK\r\n";
        $string .= "Content-Type: text/html;charset=utf-8\r\n";
        $string .= "Connection: keep-alive\r\n";
        $string .= "Content-Length: " . strlen($content) . "\r\n\r\n";
        fwrite($client, $string . $content);

        //fclose($client);


        //当socket断开连接，删除事件

        //$event->del();//删除事件


    });
    $event->add(); //加入事件监听
    $base->loop();

    //监视客户端
    //$event->del();//删除事件
});
$event->add(); //加入事件监听
var_dump($base->loop()); //调度挂起事件监听

function p(&$a) {

}




