<?php
require_once './Event/EventInterface.php';
require_once './WebSocket.php';
require_once 'Utils.php';

class Connection
{
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
    public $handshakeCompleted = false;

    /**
     * 接收到的数据
     *
     * @var string|array
     */
    public $recvBuffer;

    /**
     * 握手回调函数
     *
     * @var callback
     */
    public $onHandshake;

    /**
     * 接收到数据时的回调
     *
     * @var callback
     */
    public $onMessage;

    /**
     * 关闭连接时的回调
     *
     * @var callback
     */
    public $onClose;

    /**
     * 连接的ID
     *
     * @var string
     */
    public $clientId;

    /**
     * Connection constructor.
     * @param object $socket
     */
    public function __construct($socket)
    {
        $this->_socket = $socket;
        //生成ID
        $this->clientId = $this->getClientId();
        //设置socket为非阻塞
        socket_set_nonblock($this->_socket);
        //添加事件监听
        WebSocket::$globalEvent->add($this->_socket, array($this, 'baseRead'), EventInterface::EVENT_TYPE_READ);
    }

    /**
     * 销毁连接
     *
     * @return bool
     */
    public function destroy()
    {
        //移出事件监听
        WebSocket::$globalEvent->del($this->_socket);
        //关闭连接
        socket_close($this->_socket);
        //移出连接池的连接
        unset(Websocket::$clientConnections[$this->clientId]);
        if ($this->onClose) {
            call_user_func($this->onClose, $this);
        }

        return true;
    }

    /**
     * 读取客户端的数据
     *
     * @param object $socket
     * @return bool
     */
    public function baseRead($socket)
    {
        $readBuffer = '';
        $len = socket_recv($socket, $readBuffer, 2048, 0);
        //如果没有接收到数据就关闭连接
        if (!$len) {
            return $this->destroy();
        }

        //如果设置了握手回调则执行回调
        if (isset($this->onHandshake) && !$this->handshakeCompleted) {
            $this->handshakeCompleted = true;
            return call_user_func($this->onHandshake, $this, $readBuffer);
        }

        //接收客户端发送的数据并执行回调
        $data = Utils::decode($readBuffer);

        return call_user_func($this->onMessage, $this->clientId, $data);
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