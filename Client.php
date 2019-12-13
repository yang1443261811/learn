<?php

class Client
{
    public $host = '127.0.0.1';

    public $port = 9999;

    public $socket;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->init();
    }


    public function init()
    {
        //创建socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        //链接到服务端
        socket_connect($this->socket, $this->host, $this->port);

        $buf = '';
        $from = '';
        // will block to wait server response
        $bytes_received = socket_recv($this->socket, $buf, 65536, MSG_WAITALL);
        $response = $this->parse($buf);
        print_r($response);

        //关闭socket
        socket_close($this->socket);
    }

    public function parse($text)
    {
        if ($this->isJson($text)) {
            return json_decode($text, true);
        } else {
            return $text;
        }
    }

    public function isJson($str)
    {
        if (!$str) return false;

        json_decode($str);

        return json_last_error() == JSON_ERROR_NONE;
    }
}

new Client('127.0.0.1', 9999);

