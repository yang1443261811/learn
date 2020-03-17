<?php
// 存储子进程pid
$pids = [];
// 最大进程数
$MAX_PROCESS = 3;
$pid = pcntl_fork();
switch ($pid) {
    case -1:
        exit("fork fail\n");
    case 0:
        // 从当前终端分离
        if (posix_setsid() == -1) {
            exit("could not detach from terminal\n");
        }

        @cli_set_process_title('php: master process');
        $id = getmypid();
        echo time() . " Master process, pid {$id}\n";
        $i = 0;
        while ($i < $MAX_PROCESS) {
            start_worker_process();
            $i++;
        }

        // Master进程等待子进程退出，必须是死循环
        while (1) {
            foreach ($pids as $pid) {
                if ($pid) {
                    $res = pcntl_waitpid($pid, $status, WNOHANG);
                    if ($res == -1 || $res > 0) {
                        echo time() . " Worker process $pid exit, will start new... \n";
                        start_worker_process();
                        unset($pids[$pid]);
                    }
                }
            }
        }
        break;
    default:
        // 父进程退出
        exit('Parent process exit\n');
}

/**
 * 创建worker进程
 */
function start_worker_process()
{
    global $pids;
    $pid = pcntl_fork();
    if ($pid < 0) {
        exit("fork fail\n");
    } elseif ($pid > 0) {
        $pids[$pid] = $pid;
        // exit;此处不可退出，否则Master进程就退出了
    } else {
        //实际代码
        @cli_set_process_title('php: worker process');
        $id = getmypid();
        $rand = rand(1, 3);
        echo time() . " Worker process, pid {$id}. run $rand s\n";
        while (1) {
            sleep($rand);
        }
    }
}