<?php

require_once 'EventInterface.php';

class Select implements EventInterface
{
    // 读资源列表
    public $readResources = [];
    // 写资源列表
    public $writeResources = [];
    // 读事件列表
    public $readEventQueue = [];
    // 写事件列表
    public $writeEventQueue = [];

    // 初始化
    public function __construct()
    {

    }

    /**
     * 添加事件
     *
     * @param $resource resource|int 读写事件中表示socket资源,定时器任务中表示时间(int,秒),信号回调中表示信号(int)
     * @param $func string|array 回调函数
     * @param $type int 类型
     * @param $args array 回调函数的参数
     * @return void
     */
    public function add($resource, $func, $type, array $args = [])
    {
        $id = (int)$resource;
        if ($type == self::EVENT_TYPE_READ) {
            $this->readResources[$id] = $resource;
            $this->readEventQueue[$id] = [$func, $args];
        } else if ($type == self::EVENT_TYPE_WRITE) {
            $this->writeResources[$id] = $resource;
            $this->writeEventQueue[$id] = [$func, $args];
        }
    }

    /**
     * 删除指定的事件
     *
     * @param $resource resource|int 读写事件中表示socket资源,定时器任务中表示时间(int,秒),信号回调中表示信号(int)
     * @param $type int 类型
     */
    public function del($resource, $type = null)
    {
        $id = (int)$resource;
        unset($this->readResources[$id], $this->readEventQueue[$id]);
    }

    /**
     * 清除所有的计时器事件
     */
    public function delAllTimer()
    {

    }

    /**
     * 循环事件
     */
    public function loop()
    {
        while (true) {
            $except = null;
            $read = $this->readResources;
            $write = $this->writeResources;
            $selectNum = socket_select($read, $write, $except, null);
            if (!$selectNum) {
                continue;
            }

            foreach ($read as $connect) {
                $id = (int)$connect;
                list($callback, $args) = $this->readEventQueue[$id];
                call_user_func($callback, $connect);
            }
        }
    }
}