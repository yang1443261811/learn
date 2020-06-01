<?php

namespace App;
class Chat
{
    protected static $connections = array();

    protected static $users = array();

    public static function join($client)
    {
        $client_id = uniqid();
        static::$connections[$client_id] = $client;

        return $client_id;
    }

    public static function bindUid($client_id, $uid)
    {
        $users[$uid] = $client_id;
    }

    public static function getConnections()
    {
        return static::$connections;
    }

    public static function sendToUid($uid, array $data)
    {
        $client_id = static::$users[$uid];
        $client = static::$connections[$client_id];

        $client->push(json_encode($data));
    }

    public static function sendToClient($client_id, array $data)
    {
        $client = static::$connections[$client_id];

        $client->push(json_encode($data));
    }

    public static function isOnline($uid)
    {
        return isset(static::$users[$uid]);
    }

    public static function broadcast(array $data)
    {
        $message = json_encode($data);
        foreach (static::$connections as $client) {
            $client->push($message);
        }
    }
}