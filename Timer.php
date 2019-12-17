<?php


class Timer
{
    /**
     * 任务列表
     *
     * @var array
     */
    protected static $tasks = [];

    /**
     * 初始化
     *
     * @return void
     */
    public static function init()
    {
        //安装信号处理方法
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGALRM, array('Timer', 'signalHandle'), false);
        }
    }

    /**
     * 接收到信号执行处理
     *
     * @return void
     */
    public static function signalHandle()
    {
        pcntl_alarm(1);
        self::tick();
    }

    /**
     * 对任务列表进行循环,执行满足条件的任务
     *
     * @return void
     */
    public static function tick()
    {
        //没有任务,返回
        if (empty(static::$tasks)) {
            return;
        }

        foreach (static::$tasks as $time => $arr) {
            $current = time();
            //如果满足执行条件,遍历每一个任务
            if ($current >= $time) {
                foreach ($arr as $k => $job) {
                    //回调函数
                    $func = $job['func'];
                    //回调函数参数
                    $argv = $job['argv'];
                    //时间间隔
                    $interval = $job['interval'];
                    //持久化
                    $persist = $job['persist'];
                    //当前时间有执行任务
                    //调用回调函数,并传递参数
                    call_user_func_array($func, $argv);
                    //如果做持久化,则写入数组,等待下次唤醒
                    if ($persist) {
                        static::$tasks[$current + $interval][] = $job;
                    }
                }

                //删除该任务
                unset(static::$tasks[$time]);
            }
        }
    }


    /**
     * 添加任务
     *
     * @param $interval
     * @param $func
     * @param array $argv
     * @param bool $persist
     */
    public static function add($interval, $func, $argv = array(), $persist = false)
    {
        if (is_null($interval)) {
            return;
        }

        if (empty(static::$tasks)) {
            \pcntl_alarm(1);
        }

        $run_time = time() + $interval;
        //写入定时任务
        static::$tasks[$run_time][] = array(
            'func'     => $func,
            'argv'     => $argv,
            'interval' => $interval,
            'persist'  => $persist
        );
    }
}

(new Timer())->init();