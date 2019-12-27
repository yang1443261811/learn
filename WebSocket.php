<?php
require_once './Utils.php';
require_once './Connection.php';
require_once './Event/Select.php';
require_once './Event/CustomEvent.php';
require_once './Event/EventInterface.php';


class WebSocket
{
    /**
     * 监听的地址
     *
     * @var string
     */
    private $host;

    /**
     * 监听的端口号
     *
     * @var int
     */
    private $port;

    /**
     * socket链接池
     *
     * @var array
     */
    public static $clientConnections = [];

    /**
     * socket 主服务
     *
     * @var
     */
    protected $master;

    /**
     * 接入的用户表
     *
     * @var array
     */
    protected $users = [];

    /**
     * 事件对象
     *
     * @var object
     */
    public static $globalEvent;

    /**
     * 事件回调
     *
     * @var callable
     */
    public $onConnect;

    /**
     * 事件回调
     *
     * @var callable
     */
    public $onMessage;

    /**
     * Server constructor.
     * @param string $host
     * @param int $port
     */
    public function __construct($host = '127.0.0.1', $port = 9999)
    {
        $this->port = $port;
        $this->host = $host;
        static::$globalEvent = $this->getEventPollClass('event');
    }

    public function run()
    {
        //创建socket
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        //返回bool.套接字resource,协议级别,可用的socket选项,值。
        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);

        //将socket绑定到指定的IP:port上
        socket_bind($this->master, $this->host, $this->port);

        //开始监听
        socket_listen($this->master);

        //将服务器设置为非阻塞
        socket_set_nonblock($this->master);

        static::$globalEvent->add($this->master, [$this, 'connect'], EventInterface::EVENT_TYPE_READ);

        static::$globalEvent->loop();
    }

    public function getEventPollClass($type = null)
    {
        if ($type == 'event') {
            $instance = new CustomEvent();
        } else {
            $instance = new Select();
        }

        return $instance;
    }

    /**
     * 将socket加入链接池
     *
     * @param object $socket
     */
    public function connect($socket)
    {
        //接收一个链接
        $connection = socket_accept($socket);
        if ($connection) {
            //初始化新的连接
            $newConnection = new Connection($connection, static::$globalEvent);
            $newConnection->onMessage = array($this, 'onMessage');
            $newConnection->onHandshake = array($this, 'handshake');
            static::$clientConnections[$newConnection->clientId] = $newConnection;
        } else {
            $err_code = socket_last_error();
            $err_msg = socket_strerror($err_code);
            $this->error(['error', $err_code, $err_msg]);
        }
    }

    public function onMessage($client_id, $data)
    {
        static::$clientConnections[$client_id]->send($data);

        return true;
    }

    /**
     * 回应握手
     *
     * @param object $connection
     * @param string $buffer
     * @return bool
     */
    public function handshake($connection, $buffer)
    {
        //对接收到的buffer处理,并回馈握手！！
        $buf = substr($buffer, strpos($buffer, 'Sec-WebSocket-Key:') + 18);
        $key = trim(substr($buf, 0, strpos($buf, "\r\n")));
        $hash = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Sec-WebSocket-Version: 13\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Accept: " . $hash . "\r\n\r\n";
        //回馈握手
        socket_write($connection->_socket, $response, strlen($response));

        return true;
    }


    /**
     * 记录debug信息
     *
     * @param array $info
     */
    private function error(array $info)
    {
        $time = date('Y-m-d H:i:s');
        array_unshift($info, $time);
        $info = array_map('json_encode', $info);
        file_put_contents('./websocket_debug.log', implode(' | ', $info) . "\r\n", FILE_APPEND);
    }

}

$webSocket = new WebSocket();
$webSocket->run();