<?php
use App\Lib\Router;

Router::get('/fuck', 'IndexController@index');


//Router::get('(:all)', function($fu) {
//    echo '未匹配到路由<br>'.$fu;
//});

