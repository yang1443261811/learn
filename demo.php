<?php

class EventLib
{
    public $_allEvents = array();

    public $eventBase;

    public function __construct()
    {
        $this->eventBase = new EventBase();
    }

    public function add($fd, $func, $args = array())
    {
        $fd_key = (int)$fd;
        $event = new \Event($this->eventBase, $fd, \Event::READ | \Event::PERSIST, $func, $fd);
        if (!$event || !$event->add()) {
            return false;
        }
        $this->_allEvents[$fd_key] = $event;
        return true;
    }

    public function loop()
    {
        $this->eventBase->loop();
    }
}


$host = '0.0.0.0';
$port = 9999;
$listen_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

socket_bind($listen_socket, $host, $port);

socket_listen($listen_socket);

echo PHP_EOL . PHP_EOL . "Http Server ON : http://{$host}:{$port}" . PHP_EOL;

socket_set_nonblock($listen_socket);

$eventLib = new EventLib();
$eventLib->add($listen_socket, function ($listen_socket) {
    if (($connect_socket = socket_accept($listen_socket)) != false) {
        echo "有新的客户端：" . intval($connect_socket) . PHP_EOL;
        $msg = "HTTP/1.0 200 OK\r\nContent-Length: 2\r\n\r\nHi";
        socket_write($connect_socket, $msg, strlen($msg));
        socket_close($connect_socket);
    }
}, $listen_socket);

$eventLib->loop();
