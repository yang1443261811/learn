<?php
require_once 'EventInterface.php';
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
     * Connection constructor.
     * @param object $socket
     * @param EventInterface $event
     */
    public function __construct($socket, EventInterface $event)
    {
        $this->_socket = $socket;
        //设置socket为非阻塞
        socket_set_nonblock($this->_socket);
        //事件处理类
        $this->_event = $event;
        //添加事件监听
        $this->_event->add($this->_socket, array($this, 'reader'), EventInterface::EVENT_TYPE_READ);
    }

    /**
     * 销毁连接
     *
     * @return void
     */
    public function destroy()
    {
        //关闭连接
        socket_close($this->_socket);
        //移出事件监听
        $this->_event->del($this->_socket);
    }

    /**
     * 读取客户端的数据
     *
     * @param object $socket
     */
    public function reader($socket)
    {
        $bytes = @socket_recv($socket, $buffer, 2048, 0);
        if (!$bytes) {
            $this->destroy();
        } else {
            //是否握手
            if ($this->handshake == 0) {
                $this->handshake($buffer);
                $this->handshake = 1;
            } else {
                $data = Utils::decode($buffer);
                $content = Utils::encode(json_encode($data));
                socket_write($socket, $content, strlen($content));
                //执行事件回调
//                if (is_callable($this->onMessage)) {
//                    call_user_func($this->onMessage, $data);
//                }
            }
        }
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
        //向客户端发送握手成功消息
        $msg = Utils::encode(json_encode([
            'content' => 'done',
            'type'    => 'handshake',
        ]));
        socket_write($this->_socket, $msg, strlen($msg));

        return true;
    }

}