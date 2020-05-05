<?php
use App\Lib\Router;

Router::get('/fuck', 'App\Controllers\IndexController@index');
Router::get('/dashboard', 'App\Controllers\HomeController@dashboard');


//Router::get('(:all)', function($fu) {
//    echo '未匹配到路由<br>'.$fu;
//});

