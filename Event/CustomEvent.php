<?php
require_once '../Utils.php';
require_once 'EventInterface.php';

class CustomEvent implements EventInterface
{
    public $allEvents = array();
    // 事件核心
    public $eventBase;

    // 初始化
    public function __construct()
    {
        $this->eventBase = new \EventBase();
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
        $event = new \Event($this->eventBase, $resource, Event::READ | Event::PERSIST, $func, $resource);
        $key = (int)$resource;
        $this->allEvents[$key] = $event;

        $event->add();
    }

    /**
     * 删除指定的事件
     *
     * @param $resource resource|int 读写事件中表示socket资源,定时器任务中表示时间(int,秒),信号回调中表示信号(int)
     * @param $type int 类型
     */
    public function del($resource, $type = null)
    {
        $key = (int)$resource;
        unset($this->allEvents[$key]);
        Utils::log(["call del key:$key"]);
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
        $this->eventBase->loop();
    }
}