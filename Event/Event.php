<?php

namespace Socket\Event;

class Event implements EventInterface
{
    // 读资源列表
    public $readResources = [];
    // 写资源列表
    public $writeResources = [];
    // 读事件列表
    public $readEventQueue = [];
    // 写事件列表
    public $writeEventQueue = [];
    // 事件核心
    public $eventBase;
    // 事件实例
    public $eventInstance;

    // 初始化
    public function __construct()
    {
        $this->eventBase = new EventBase();
    }

    /**
     * 添加事件
     *
     * @param $callback string|array 回调函数
     * @param $args array 回调函数的参数
     * @param $resource resource|int 读写事件中表示socket资源,定时器任务中表示时间(int,秒),信号回调中表示信号(int)
     * @param $type int 类型
     * @return bool
     */
    public function add($callback, array $args, $resource, $type)
    {
        $event = new Event($this->eventBase, $resource, Event::READ | Event::PERSIST, function ($parameters) {
            call_user_func($parameters[0], $parameters[1]);
        }, [$callback, $resource]);

        $event->add();
    }

    /**
     * 删除指定的事件
     *
     * @param $resource resource|int 读写事件中表示socket资源,定时器任务中表示时间(int,秒),信号回调中表示信号(int)
     * @param $type int 类型
     */
    public function delOne($resource, $type)
    {

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