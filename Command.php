<?php

class Command
{
    public static $isDomain = false;

    public static function run()
    {
        static::parseCommand();
        static::worker();
    }

    public static function parseCommand()
    {
        global $argv;
        $filename = trim($argv[0]);
        $operation = trim($argv[1]);
        $isDomain = isset($argv[2]) && trim($argv[2]) === '-d' ? true : false;
        switch ($operation) {
            case 'start':
                static::start($isDomain);
                break;
            case 'stop':
                static::stop();
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
            static::$isDomain = true;
            static::_daemon();
        }
    }

    public static function stop()
    {
        echo 'stop';
    }

    public static function restart()
    {
        echo 'restart';
    }

    public static function state()
    {
        echo 'state';
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
