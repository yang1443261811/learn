<?php

namespace App\Controllers;

class IndexController
{
    public function index($request)
    {
        return json_encode([
            'code'    => 200,
            'message' => 'successfuly',
            'data'    => []
        ]);
    }
}