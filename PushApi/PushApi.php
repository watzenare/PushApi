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

        // Only invoked if mode is "production"
        $this->app->configureMode('production', function () use ($app) {
            $app->config(array(
                'debug' => false,
                'log.enable' => true,
                'log.level' => \Slim\Log::WARN,
                'log.writer' => new \Slim\LogWriter(fopen(__DIR__ . DIRECTORY_SEPARATOR . 'System/oficialLog.txt', 'a'))
            ));
        });

        // Only invoked if mode is "development"
        $this->app->configureMode('development', function () use ($app) {
            $app->config(array(
                'debug' => true,
                'log.enable' => false,
                'log.level' => \Slim\Log::DEBUG,
                'log.writer' => new \Slim\LogWriter(fopen(__DIR__ . DIRECTORY_SEPARATOR . 'System/debugLog.txt', 'a'))
            ));
        });

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

                case PushApiException::INVALID_DATA:
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
        });
        // Custom not found calls handler
        $this->app->notFound(function () {
            $this->app->response()->header('X-Status-Reason', 'Not found');
        });
    }
}