<?php

class Command
{
    public static $is_domain = false;

    public static $masterPid;

    public static $pidFile;

    public static function init()
    {
        if (static::$pidFile) {
            $unique_prefix = uniqid();
            static::$pidFile = "./$unique_prefix.pid";
        }
    }

    public static function run()
    {
        static::init();
        static::parseCommand();
        static::saveMasterPid();
        static::worker();
    }

    public static function parseCommand()
    {
        global $argv;
        $operation = trim($argv[1]);
        $is_domain = isset($argv[2]) && trim($argv[2]) === '-d' ? true : false;

        $master_pid = \is_file(static::$pidFile) ? \file_get_contents(static::$pidFile) : 0;
        $master_is_alive = $master_pid && \posix_kill($master_pid, 0);
        if ($operation == 'start' && $master_is_alive) {
            exit("current is running\n");
        }
        if (!$master_is_alive && in_array($operation, ['state', 'stop', 'restart'])) {
            exit("current is not running\n");
        }

        switch ($operation) {
            case 'start':
                static::start($is_domain);
                break;
            case 'stop':
                static::stop($master_pid);
                break;
            case 'restart':
                static::restart();
                break;
            case 'state':
                static::state();
                break;
        }
    }

    public static function start($isDomain)
    {
        if ($isDomain) {
            static::$is_domain = true;
            static::_daemon();
        }
    }

    public static function stop($masterPid)
    {
        echo "stop running server\n";
        //给当前正在运行的主进程发送终止信号SIGINT(ctrl+c)
        if ($masterPid) {
            posix_kill($masterPid, SIGINT);
        }
        $nowTime = time();
        $timeout = 5;
        while (true) {
            //主进程是否在运行
            $masterIsAlive = $masterPid && posix_kill($masterPid, SIG_DFL);
            if ($masterIsAlive) {
                //如果超时
                if ((time() - $nowTime) > $timeout) {
                    echo "stop master process failed: timeout {$timeout} s \n";
                }
                //等待10毫秒,再次判断是否终止.
                usleep(10000);
                continue;
            }
            break;
        }
        exit("server Stop: \033[40G[\033[49;32;5mOK\033[0m]\n");
    }

    public static function restart()
    {
        echo 'restart';
    }

    public static function state()
    {
        echo 'state';
    }

    public static function saveMasterPid()
    {
        static::$masterPid = \posix_getpid();
        if (false === \file_put_contents(static::$pidFile, static::$masterPid)) {
            throw new Exception('can not save pid to ' . static::$pidFile);
        }
    }


    public static function _daemon()
    {
        //文件掩码清0
        umask(0);
        //创建一个子进程
        $pid = pcntl_fork();
        //fork失败
        if ($pid === -1) {
            echo "_daemon: fork failed \n";
            //父进程
        } else if ($pid > 0) {
            exit("parent logout \n");
        }
        //设置子进程为Session leader, 可以脱离终端工作.这是实现daemon的基础
        if (posix_setsid() === -1) {
            echo " _daemon: set sid failed \n";
        }
        //再次在开启一个子进程
        //这不是必须的,但通常都这么做,防止获得控制终端.
        $pid = pcntl_fork();
        if ($pid === -1) {
            echo "_daemon: fork2 failed";
            //将父进程退出
        } else if ($pid !== 0) {
            exit();
        }
    }

    public static function worker()
    {
        $socket = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);
        if (!$socket) {
            echo "$errstr ($errno)<br />\n";
        } else {
            while (1) {
                echo "current time top " . time() . "\r\n";
                $conn = @stream_socket_accept($socket);
                if ($conn) {
                    fwrite($conn, 'The local time is ' . date('n/j/Y g:i a') . "\n");
                    fclose($conn);
                }
            }
            fclose($socket);
        }
    }
}

Command::run();
