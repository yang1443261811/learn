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
     * socket实例
     *
     * @var
     */
    protected $socket;

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
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        //将socket绑定到指定的IP:port上
        socket_bind($this->socket, $this->host, $this->port);

        //开始监听
        socket_listen($this->socket);

        //等待客户端的链接
        $this->watch();
    }

    /**
     * 等待客户端的链接
     *
     * @return void
     */
    private function watch()
    {
        //进入while,程序不会进入死循环,因为程序会阻塞在socket_accept()函数上
        while (true) {
            $connection_socket = socket_accept($this->socket);
            //接收到客户端的链接后,向客户端发送一条消息
            $this->emit($connection_socket, [
                'id'      => 1101,
                'name'    => '静静',
                'message' => '借我500块钱',
            ]);

            socket_close($connection_socket);
        }

        socket_close($this->socket);
    }

    /**
     * 向客户端发送消息
     *
     * @param object $to_socket
     * @param array|string $message
     */
    public function emit($to_socket, $message)
    {
        if (is_array($message)) {
            $message = json_encode($message);
        }

        socket_write($to_socket, $message, strlen($message));
    }
}

$server = new Server();
$server->run();


