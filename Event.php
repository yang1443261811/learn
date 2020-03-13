<?php

class Event
{
    public function onConnect($client_id)
    {
        Server::sendToClient($client_id, json_encode([
            'type'      => 'init',
            'client_id' => $client_id
        ]));
    }

    public function onMessage($client_id, array $data)
    {
        switch ($data['type']) {
            case 'bind':
                Server::bindUid($data['uid'], $client_id);
                break;
            case 'say':
                Server::sendToUid($data['toUid'], json_encode($data));
                break;
            default:
                Server::broadcast(json_encode($data));
                break;
        }
    }

    public function onClose($client_id)
    {

    }
}