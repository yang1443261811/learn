<?php

class WebSocket
{
    public $users;

    public $server;

    public function __construct()
    {
        $this->makeUsersTable();

        $this->server = new Swoole\WebSocket\Server("0.0.0.0", 9501);

        $this->server->set(array(
            'task_worker_num' => 4,
        ));

        $this->server->on('open', [$this, 'open']);

        $this->server->on('message', [$this, 'message']);

        $this->server->on('close', [$this, 'close']);

        $this->server->tick(5000, [$this, 'sysOnlineCount']);

        $this->server->on('task', [$this, 'task']);

        $this->server->on('finish', [$this, 'finish']);

        $this->server->start();
    }

    public function task($server, $task_id, $from_id, $data)
    {
        $server->push($data['fd'], $data['content']);
    }

    public function finish($server, $task_id, $data)
    {

    }

    public function sysOnlineCount()
    {
        $users = $this->users;
        $count = $users->count();
        if ($count > 0) {
            $message = json_encode(['type' => 'sysOnlineCount', 'count' => $count]);
            foreach ($users as $item) {
                $this->server->task(['fd' => $item['fd'], 'content' => $message]);
            }
        }
    }

    public function join($uid, $fd)
    {
        $this->users->set($uid, ['fd' => $fd]);
    }

    public function sendToUid($uid, array $data)
    {
        if ($this->users->exist($uid)) {
            $fd = $this->users->get($uid, 'fd');
            $this->server->push($fd, json_encode($data));
        }
    }

    public function broadcast(array $data)
    {
        $users = $this->users;
        $message = json_encode($data);
        foreach ($users as $item) {
            $this->server->push($item['fd'], $message);
        }
    }

    public function makeUsersTable()
    {
        $this->users = new Swoole\Table(1024);
        $this->users->column('fd', Swoole\Table::TYPE_INT);
        $this->users->create();
    }

    public function open($server, $request)
    {
        $this->server->push($request->fd, json_encode([
            'type'      => 'init',
            'client_id' => 'success',
        ]));
    }

    public function message($server, $frame)
    {
        $data = json_decode($frame->data);
        switch ($data->type) {
            case 'join':
                $this->join($data->uid, $frame->fd);
                break;
            case 'talk':
                $message = [
                    'type'     => 'talk',
                    'content'  => $data->content,
                    'sender'   => $data->sender,
                    'from_uid' => $data->from_uid,
                    'date'     => date('Y-m-d H:i:s'),
                ];
                $this->sendToUid($data->to_uid, $message);
                break;
        }
    }

    public function close($server, $fd)
    {
        $users = $this->users;
        foreach ($users as $key => $item) {
            if ($item['fd'] === $fd) {
                $this->users->del($key);
                break;
            }
        }
    }
}

new WebSocket();