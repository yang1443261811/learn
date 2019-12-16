<?php


class Utils
{
    /**
     * 通讯地址到 client_id 的转换
     *
     * @param int $local_ip
     * @param int $local_port
     * @param int $connection_id
     * @return string
     */
    public static function addressToClientId($local_ip, $local_port, $connection_id)
    {
        return bin2hex(pack('NnN', $local_ip, $local_port, $connection_id));
    }

    /**
     * client_id 到通讯地址的转换
     *
     * @param string $client_id
     * @return array
     * @throws Exception
     */
    public static function clientIdToAddress($client_id)
    {
        if (strlen($client_id) !== 20) {
            echo new Exception("client_id $client_id is invalid");
            return false;
        }

        return unpack('Nlocal_ip/nlocal_port/Nconnection_id', pack('H*', $client_id));
    }

    /**
     * 将普通信息组装成websocket数据帧
     *
     * @param string $msg
     * @return string
     */
    public static function encode($msg)
    {
        $frame = [];
        $frame[0] = '81';
        $len = strlen($msg);
        if ($len < 126) {
            $frame[1] = $len < 16 ? '0' . dechex($len) : dechex($len);
        } else if ($len < 65025) {
            $s = dechex($len);
            $frame[1] = '7e' . str_repeat('0', 4 - strlen($s)) . $s;
        } else {
            $s = dechex($len);
            $frame[1] = '7f' . str_repeat('0', 16 - strlen($s)) . $s;
        }

        $data = '';
        $l = strlen($msg);
        for ($i = 0; $i < $l; $i++) {
            $data .= dechex(ord($msg{$i}));
        }

        $frame[2] = $data;
        $data = implode('', $frame);

        return pack("H*", $data);
    }

    /**
     * 解析websocket数据帧
     *
     * @param string $buffer
     * @return bool|string
     */
    public static function decode($buffer)
    {
        $decoded = '';
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }

        return json_decode($decoded, true);
    }

}