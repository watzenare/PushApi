<?php

namespace PushApi;

use \Slim\Slim;
use \PushApi\PushApiException;
use \Pimple\Container;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Main API class that configures the framework and handles exceptions
 * that could happen while the API is running.
 */
class PushApi
{
    const SLIM = 'slim';
    const REDIS = 'redis';

    /**
     * HTTP Headers
     */
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_NO_CONTENT = 204;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_METHOD_NOT_ACCEPTABLE = 406;
    const HTTP_CONFLICT = 409;
    const HTTP_INTERNAL_SERVER_ERROR = 500;

    private $app;
    private static $container;

    public function __construct(\Slim\Slim $app) {
        $this->app = $app;

        // Only invoked if mode is "production"
        $this->app->configureMode('production', function () use ($app) {
            $app->config(array(
                'debug' => false,
            ));
        });

        // Only invoked if mode is "development"
        $this->app->configureMode('development', function () use ($app) {
            $app->config(array(
                'debug' => true,
            ));
        });

        $this->startErrorHandling();
        self::$container = $this->fillContainer();
    }

    /**
     * Customized error handler. It displays the right HTTP header response if the
     * API generates some kind of exeption while running.
     */
    private function startErrorHandling() {
        $this->app->error(function (PushApiException $e) {
            switch ($e->getCode()) {
                case PushApiException::NOT_FOUND:
                    $this->app->response()->status(self::HTTP_NOT_FOUND);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::INVALID_DATA:
                case PushApiException::INVALID_RANGE:
                case PushApiException::INVALID_OPTION:
                case PushApiException::LIMIT_EXCEEDED:
                case PushApiException::DUPLICATED_VALUE:
                    $this->app->response()->status(self::HTTP_CONFLICT);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::NO_DATA:
                case PushApiException::INVALID_CALL:
                case PushApiException::INVALID_ACTION:
                    $this->app->response()->status(self::HTTP_METHOD_NOT_ALLOWED);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::NOT_AUTHORIZED:
                    $this->app->response()->status(self::HTTP_UNAUTHORIZED);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                default:
                    $this->app->response()->status(self::HTTP_INTERNAL_SERVER_ERROR);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;
            }
        });
        // If a call doesn't exist it is custmoized the not found
        // (default not found HTTP header) result message
        $this->app->notFound(function () {
            $this->app->response()->header('X-Status-Reason', "Call doesn't exist on PushApi");
        });
    }

    /**
     * Adds into a container all the services that the API requires.
     * @return [Container] The Container object fully created
     */
    private function fillContainer()
    {
        $c = new Container();

        $c[PushApi::SLIM] = function ($c) {
            return \Slim\Slim::getInstance();
        };

        $c[PushApi::REDIS] = function ($c) {
            return new \Credis_Client(REDIS_IP);
        };

        return $c;
    }

    /**
     * [setContainerParameter description]
     * @param [type] $parameter [description]
     * @param [type] $value     [description]
     */
    public static function setContainerParameter($parameter, $value)
    {
        self::$container[$parameter] = $value;
    }

    /**
     * [getContainerService description]
     * @param  [type] $serviceName [description]
     * @return [type]              [description]
     */
    public static function getContainerService($serviceName)
    {
        return self::$container[$serviceName];
    }

    /**
     * [getContainer description]
     * @return [type] [description]
     */
    public static function getContainer()
    {
        return self::$container;
    }
}