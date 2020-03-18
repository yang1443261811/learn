<?php

class Worker
{

    public static $count = 2;

    public static function runAll()
    {
        static::installHandler();

        static::runMaster();

        static::monitor();
    }

    //开启主进程
    public static function runMaster()
    {
        //确保进程有最大操作权限
        umask(0);
        $pid = pcntl_fork();
        switch ($pid) {
            case -1 :
                exit("parent process fork fail\n");
            case 0:
                if (-1 === posix_setsid()) {
                    throw new Exception("could not detach from terminal\n");
                }

                @cli_set_process_title("php: master process");
                $i = 1;
                while ($i < self::$count) {
                    static::runWorker();
                    $i++;
                }

//                while (1) {
//                    sleep(1);
//
//                }
                break;
            default:
                exit("Parent process exit\n");
        }
    }

    //开启子进程
    public static function runWorker()
    {
        umask(0);
        $pid = pcntl_fork();
        switch ($pid) {
            case -1 :
                exit("child process fork fail\n");
            case 0:
                if (-1 === posix_setsid()) {
                    throw new Exception("could not detach from terminal\n");
                }

                @cli_set_process_title("php: worker process");

                while (1) {
                    sleep(1);
                }

                break;
            default:
                // exit;此处不可退出，否则Master进程就退出了
        }
    }

    /**
     * 安装一个信号处理方法
     */
    public static function installHandler()
    {
        pcntl_signal(SIGCHLD, array('Worker', 'signalHandler'));
    }

    public static function signalHandler($pid)
    {
//        echo "收到子进程退出(pid:$pid)" . PHP_EOL;
//        $result = pcntl_waitpid($pid, $status, WNOHANG);
//        $pid = pcntl_wait($status, WUNTRACED);

        static::runWorker();
    }

    /**
     * 主进程陷入monitor子进程循环中.
     */
    public static function monitor()
    {
        while (true) {
            sleep(1);
            // 触发信号处理
            pcntl_signal_dispatch();
            // 挂起父前进程直到子进程收到中断信号
            $pid = pcntl_wait($status, WUNTRACED);
            // 触发信号处理
            pcntl_signal_dispatch();
        }
    }
}

Worker::runAll();
