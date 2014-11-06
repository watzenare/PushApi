<?php

namespace PushApi\Controllers;


class Controller
{
    protected $slim;

    public function __construct($slim) {
        $this->slim = $slim;
    }

    protected function send($result)
    {
        $response = $this->slim->response();

        $response->header('Content-Type', JSON);

        $response->body(json_encode($result));
    }
}