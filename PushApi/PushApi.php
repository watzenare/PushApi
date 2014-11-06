<?php

namespace PushApi;

use \Slim\Slim;
use \PushApi\PushApiException;
use \PushApi\System\Util;
use \PushApi\System\LogWriter;

class PushApi
{
    private $slim;

    public function __construct($config) {

        $this->setSlim($config);

        // Charging the API routes
        require "System/Routes.php";

        $this->startErrorHandling();

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

    private function startErrorHandling() {
        // Custom error handler
        $this->slim->error(function (PushApiException $e) {
            switch ($e->getCode()) {
                case PushApiException::NOT_FOUND:
                    $this->slim->response()->status(HTTP_NOT_FOUND);
                    $this->slim->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::DUPLICATED_VALUE:
                    $this->slim->response()->status(HTTP_CONFLICT);
                    $this->slim->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::INVALID_CALL:
                    $this->slim->response()->status(HTTP_METHOD_NOT_ALLOWED);
                    $this->slim->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                default:
                    $this->slim->response()->status(HTTP_INTERNAL_SERVER_ERROR);
                    $this->slim->response()->header('X-Status-Reason', $e->getMessage());
                    break;
            }
            // Print custom errors and HTTP errors or only HTTP
            // $this->slim->body(json_encode(
            //     'message' => $e->getMessage(),
            //     'error' => $e->getCode()
            // ));
        });
        // Custom not found calls handler
        $this->slim->notFound(function () {
            throw new PushApiException(PushApiException::INVALID_CALL);
        });
    }
}