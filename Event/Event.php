<?php

namespace Socket\Event;

class Event implements EventInterface
{
    /**
     * 保存所有读事件
     *
     * @var array
     */
    public $readQueue = [];

    /**
     * 保存所有写事件
     *
     * @var array
     */
    public $writeQueue = [];

    /**
     * 初始化
     *
     * EventInterface constructor.
     */
    public function __construct()
    {

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

    }
}