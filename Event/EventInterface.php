<?php

interface EventInterface
{
    //读事件
    const EVENT_TYPE_READ = 1;
    //写事件
    const EVENT_TYPE_WRITE = 2;
    //永久性定时器事件
    const EVENT_TYPE_TIMER = 4;
    //一次性定时器事件
    const EVENT_TYPE_TIMER_ONCE = 8;
    //信号事件
    const EVENT_TYPE_SIGNAL = 16;

    /**
     * 初始化
     *
     * EventInterface constructor.
     */
    public function __construct();

    /**
     * 添加事件
     *
     * @param $resource resource|int 读写事件中表示socket资源,定时器任务中表示时间(int,秒),信号回调中表示信号(int)
     * @param $func string|array 回调函数
     * @param $type int 类型
     * @param $args array 回调函数的参数
     * @return bool
     */
    public function add($resource, $func, $type, array $args);

    /**
     * 删除指定的事件
     *
     * @param $resource resource|int 读写事件中表示socket资源,定时器任务中表示时间(int,秒),信号回调中表示信号(int)
     * @param $type int 类型
     */
    public function del($resource, $type = null);

    /**
     * 清除所有的计时器事件
     */
    public function delAllTimer();

    /**
     * 循环事件
     */
    public function loop();
}