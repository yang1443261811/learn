<?php


class Timer
{
    public function init()
    {
        if (\function_exists('pcntl_signal')) {
            \pcntl_signal(\SIGALRM, array('Timer', 'signalHandle'), false);
        } else {
            die('err');
        }
    }

    public function signalHandle()
    {
        \pcntl_alarm(1);
        echo 123234;
    }
}

(new Timer())->init();