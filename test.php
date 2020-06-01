<?php
function getRoleInfo()
{
    $params['app_id'] = 'bmccfg';
    $params['uuid'] = '2eb6cef9b8a849358cf119b3ba0e572e';
    ksort($params);
    $str = '';
    foreach ($params as $value) {
        $str .= $value;
    }
    $signature = md5($str . 'EWTS1XURO809DH64KPB23AQVNI5GFZJ7CMYL');
    $api = sprintf(
        'https://rolelist.morefun.zone/index.php/MoreFun/getUserInfo?appid=%s&uuid=%s&sign=%s',
        $params['app_id'],
        $params['uuid'],
        $signature
    );


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8"));
    $content = curl_exec($ch);
    if (curl_error($ch)) {
        echo curl_error($ch);
    }
    curl_close($ch);
    print_r($api);
}
//getRoleInfo();
function getPay()
{
    $params['appid'] = 'pzpkz0';
    $params['productId'] = 'com.yoyo.sqdl.pay6';
    $params['uuid'] = '2a4eb403803743a9aed0f362ad42a933';
    $params['serverId'] = 1;
    $params['roleId'] = 10001;
    ksort($params);
    $str = '';
    foreach ($params as $value) {
        $str .= $value;
    }
    $params['sign'] = md5($str . 'EWTS1XURO809DH64KPB23AQVNI5GFZJ7CMYL');

    $api = 'http://47.74.146.179:8081/fire-3sdk/payment/create/sign/10006/1000035/42';
    $api = $api . '?' . http_build_query($params);
    $ch1 = curl_init();
    curl_setopt($ch1, CURLOPT_URL, $api);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch1, CURLOPT_HTTPHEADER, array("Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8"));
    $content = curl_exec($ch1);
    print_r($content);
    curl_close($ch1);
    $content = json_decode($content, true);
}
getPay();

