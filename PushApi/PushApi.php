<?php

namespace PushApi;

use \Slim\Slim;
use \PushApi\System\Util;
use \PushApi\System\LogWriter;

class PushApi
{
    private $slim;

    public function __construct($config) {

        $this->setSlim($config);

        // Charging the API routes
        require "System/Routes.php";

        $this->run();
    }

    public function setSlim($config) {
        $this->slim = new Slim($config);
    }

    public function getSlim() {
        return $this->slim;
    }

    public function run() {
        $this->slim->run();
    }

    private function jsonize($result, $error) {
        $this->slim->response()->header('Content-Type', JSON);

        $json = json_encode(array(
            "result" => $result,
            "error" => $error
        ));

        return $json;
    }

    public function sendResponse($result, $error) {

        $data = $this->jsonize($result, $error);

        $this->slim->response()->body($data);
    }
}