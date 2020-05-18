<?php
namespace App;

class Handler
{
    protected static $server;

    public static function process($server, $client, $data)
    {
        static::$server = $server;
        $data = json_decode($data);
        switch ($data['action']) {
            case 'bind':
                static::bind($client, $data);
            case 'talk':
                static::talk($client, $data);
            default :

        }
    }

    public static function bind($client, array $data)
    {
        $client_id = \Chat::join($client);
        \Chat::bindUid($client_id, $data['uid']);
    }

    public static function talk($client, array $data)
    {

    }
}