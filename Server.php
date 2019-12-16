<?php
header('Content-Type:text/html;charset=utf-8');

class Server
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
    protected static $sockets = [];

    /**
     * socket 主服务
     *
     * @var
     */
    protected static $master;

    /**
     * 事件对象
     *
     * @var object
     */
    protected $events;

    /**
     * 接入的用户表
     *
     * @var array
     */
    protected static $users = [];

    /**
     * Server constructor.
     * @param string $host
     * @param int $port
     */
    public function __construct($host = '127.0.0.1', $port = 9999)
    {
        $this->port = $port;
        $this->host = $host;
    }

    /**
     * 运行服务
     *
     * @return void
     */
    public function run()
    {
        //创建socket
        static::$master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        //返回bool.套接字resource,协议级别,可用的socket选项,值。
        socket_set_option(static::$master, SOL_SOCKET, SO_REUSEADDR, 1);

        //将socket绑定到指定的IP:port上
        socket_bind(static::$master, $this->host, $this->port);

        //开始监听
        socket_listen(static::$master);

        static::$sockets[0] = ['resource' => static::$master];

        //等待客户端的链接
        while (true) {
            $this->watch();
        }
    }

    /**
     * 等待客户端的链接
     *
     * @return void
     */
    private function watch()
    {
        $write = null;
        $except = null;
        $sockets = array_column(static::$sockets, 'resource');
        $read_num = socket_select($sockets, $write, $except, null);
        foreach ($sockets as $socket) {
            if ($socket == static::$master) {
                $client = socket_accept($socket);
                $this->connect($client);
            } else {
                $buffer = '';
                socket_recv($socket, $buffer, 2048, 0);
                if (static::$sockets[(int)$socket]['handshake']) {
                    $data = $this->parse($buffer);
                    $this->onMessage($socket, $data);
                } else {
                    $this->handshake($socket, $buffer);
                    $this->onConnect($socket);
                }
            }
        }
    }

    /**
     * 链接成功时的处理
     *
     * @param object $socket
     */
    public function onConnect($socket)
    {
        $client_id = (int)$socket;
        $this->events->onConnect($client_id);
    }

    /**
     * 关闭链接时的处理
     *
     * @param object $socket
     */
    public function onClose($socket)
    {
        $client_id = (int)$socket;
        $this->events->onClose($client_id);
    }

    /**
     * 来消息了的处理
     *
     * @param object $socket
     * @param array $data
     */
    public function onMessage($socket, $data)
    {
        $client_id = (int)$socket;
        $this->events->onMessage($client_id, $data);
    }

    /**
     * 向指定的客户端发送信息
     *
     * @param $client_id
     * @param $data
     */
    public static function sendToClient($client_id, $data)
    {
        $socket = static::$sockets[$client_id]['resource'];
        $content = static::frame($data);

        socket_write($socket, $content, strlen($content));
    }

    /**
     * 将用户ID与client_id组成一个映射关系
     *
     * @param $uid
     * @param $client_id
     */
    public static function bindUid($uid, $client_id)
    {
        static::$users[$uid] = $client_id;
    }

    /**
     * 给指定uid的用户发送消息
     *
     * @param int $uid
     * @param string $data
     */
    public static function sendToUid($uid, $data)
    {
        $client_id = static::$users[$uid];
        $socket = static::$sockets[$client_id]['resource'];
        $content = static::frame($data);

        socket_write($socket, $content, strlen($content));
    }

    /**
     * 广播信息
     *
     * @param string $data
     */
    public static function broadcast($data)
    {
        $content = static::frame($data);
        foreach (static::$sockets as $socket) {
            if ($socket['resource'] != static::$master) {
                socket_write($socket['resource'], $content, strlen($content));
            }
        }
    }

    /**
     * 将socket加入链接池
     *
     * @param object $socket
     */
    public function connect($socket)
    {
        socket_getpeername($socket, $ip, $port);
        $socket_info = [
            'resource'  => $socket,
            'handshake' => false,
            'ip'        => $ip,
            'port'      => $port,
        ];

        static::$sockets[(int)$socket] = $socket_info;
    }

    /**
     * 回应握手
     *
     * @param object $socket
     * @param string $buffer
     * @return bool
     */
    public function handshake($socket, $buffer)
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
        socket_write($socket, $response, strlen($response));
        //设置握手的状态
        static::$sockets[(int)$socket]['handshake'] = true;
        //向客户端发送握手成功消息
        $msg = static::frame(json_encode([
            'content' => 'done',
            'type'    => 'handshake',
        ]));
        socket_write($socket, $msg, strlen($msg));

        return true;
    }

    /**
     * 将普通信息组装成websocket数据帧
     *
     * @param string $msg
     * @return string
     */
    private static function frame($msg)
    {
        $frame = [];
        $frame[0] = '81';
        $len = strlen($msg);
        if ($len < 126) {
            $frame[1] = $len < 16 ? '0' . dechex($len) : dechex($len);
        } else if ($len < 65025) {
            $s = dechex($len);
            $frame[1] = '7e' . str_repeat('0', 4 - strlen($s)) . $s;
        } else {
            $s = dechex($len);
            $frame[1] = '7f' . str_repeat('0', 16 - strlen($s)) . $s;
        }

        $data = '';
        $l = strlen($msg);
        for ($i = 0; $i < $l; $i++) {
            $data .= dechex(ord($msg{$i}));
        }

        $frame[2] = $data;
        $data = implode('', $frame);

        return pack("H*", $data);
    }

    /**
     * 解析websocket数据帧
     *
     * @param string $buffer
     * @return bool|string
     */
    private function parse($buffer)
    {
        $decoded = '';
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }

        return json_decode($decoded, true);
    }

    /**
     * 设置事件处理对象
     *
     * @param Event $event
     */
    public function setEvents(Event $event)
    {
        $this->events = $event;
    }

}

require_once './Event.php';
$server = new Server();
$server->setEvents(new Event());
$server->run();


