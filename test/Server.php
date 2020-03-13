<?php
require_once './Events.php';
class Server
{
    public $onMessage;

    public $onConnect;

    public $onClose;

    public function run()
    {
        call_user_func($this->onMessage);
        call_user_func($this->onConnect);
        call_user_func($this->onClose);
    }
}


$server = new Server();
$server->onClose = 'Events::onClose';
$server->onConnect = 'Events::onConnect';
$server->onMessage = 'Events::onMessage';
$server->run();