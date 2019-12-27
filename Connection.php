<?php
require_once './Event/EventInterface.php';
require_once 'Utils.php';

class Connection
{
    /**
     * 事件对象
     *
     * @var EventInterface
     */
    public $_event;

    /**
     * socket实例
     *
     * @var object
     */
    public $_socket;

    /**
     * 握手标识
     *
     * @var int
     */
    public $handshake;

    /**
     * 接收到数据时的回调
     *
     * @var callback
     */
    public $onMessage;

    /**
     * 连接的ID
     *
     * @var string
     */
    public $clientId;

    /**
     * Connection constructor.
     * @param object $socket
     * @param EventInterface $event
     */
    public function __construct($socket, EventInterface $event)
    {
        $this->_socket = $socket;
        //生成ID
        $this->clientId = $this->getClientId();
        //事件处理类
        $this->_event = $event;
        //设置socket为非阻塞
        socket_set_nonblock($this->_socket);
        //添加事件监听
        $this->_event->add($this->_socket, [$this, 'read'], EventInterface::EVENT_TYPE_READ);
    }

    /**
     * 销毁连接
     *
     * @return void
     */
    public function destroy()
    {
        //移出事件监听
        $this->_event->del($this->_socket);
        //关闭连接
        socket_close($this->_socket);
    }

    /**
     * 读取客户端的数据
     *
     * @param object $socket
     * @return true
     */
    public function read($socket)
    {
        $len = socket_recv($socket, $buffer, 2048, 0);
        //接收到的数据为空关闭连接
        if (!$len) {
            $err_code = socket_last_error();
            $err_msg = socket_strerror($err_code);
            $this->error(['error', $err_code, $err_msg]);
            $this->destroy();
        } else {
            //进行握手
            if ($this->handshake == 0) {
                $this->handshake($buffer);
                $this->handshake = 1;
                //向客户端发送握手成功消息
                $this->send(['content' => 'done', 'type' => 'handshake',]);
            } else {
                //接收客户端发送的数据并执行回调
                $data = Utils::decode($buffer);
                call_user_func($this->onMessage, $this->clientId, $data);
            }
        }

        return true;
    }

    /**
     * 发送数据到当前连接
     *
     * @param array $data
     */
    public function send(array $data)
    {
        $content = Utils::encode(json_encode($data));

        socket_write($this->_socket, $content, strlen($content));
    }

    /**
     * 获取客户端ID
     *
     * @return string
     */
    public function getClientId()
    {
        socket_getpeername($this->_socket, $ip, $port);
        $connect_id = (int)$this->_socket;
        $client_id = Utils::addressToClientId($ip, $port, $connect_id);

        return $client_id;
    }

    /**
     * 回应握手
     *
     * @param string $buffer
     * @return bool
     */
    protected function handshake($buffer)
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
        socket_write($this->_socket, $response, strlen($response));

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