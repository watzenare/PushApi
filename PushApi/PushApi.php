<?php

namespace PushApi;

use \Slim\Slim;
use \PushApi\PushApiException;
use \PushApi\System\Util;
use \PushApi\System\LogWriter;

class PushApi
{
    private $app;

    public function __construct(\Slim\Slim $app) {
        $this->app = $app;

        $this->startErrorHandling();
    }

    private function startErrorHandling() {
        // Custom error handler
        $this->app->error(function (PushApiException $e) {
            switch ($e->getCode()) {
                case PushApiException::NOT_FOUND:
                    $this->app->response()->status(HTTP_NOT_FOUND);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::INVALID_RANGE:
                case PushApiException::INVALID_OPTION:
                case PushApiException::DUPLICATED_VALUE:
                    $this->app->response()->status(HTTP_CONFLICT);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::NO_DATA:
                case PushApiException::INVALID_CALL:
                    $this->app->response()->status(HTTP_METHOD_NOT_ALLOWED);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::NOT_AUTORIZED:
                    $this->app->response()->status(HTTP_UNAUTHORIZED);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                default:
                    $this->app->response()->status(HTTP_INTERNAL_SERVER_ERROR);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;
            }
            // // Print custom errors and HTTP errors or only HTTP
            // $this->app->body(json_encode(
            //     'message' => $e->getMessage(),
            //     'error' => $e->getCode()
            // ));
        });
        // Custom not found calls handler
        $this->app->notFound(function () {
            $this->app->response()->header('X-Status-Reason', 'Not found');
        });
    }
}