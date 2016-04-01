<?php

namespace PushApi;

use \Slim\Log;
use \Slim\Slim;
use \Pimple\Container;
use \PushApi\System\Mail;
use \PushApi\System\Android;
use \PushApi\System\Ios;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Main API class that configures the framework and handles exceptions
 * that could happen while the API is running.
 */
class PushApi
{
    const SLIM = 'slim';
    const REDIS = 'redis';
    const MAIL = 'mail';
    const ANDROID = 'android';
    const IOS = 'ios';

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

    public function __construct($app) {
        $this->app = $app;

        self::$container = $this->fillContainer();

        if (isset($app)) {
            $this->startErrorHandling();
        }
    }

    /**
     * Customized error handler. It displays the right HTTP header response if the
     * API generates some kind of exception while running.
     */
    private function startErrorHandling() {
        // Log sample: POST - pushapi.com/user/1
        $routeInfoLog = $this->app->request->getMethod() . " - " .  $this->app->request->getHost() . $this->app->request->getPath();
        self::log($routeInfoLog, Log::ALERT);

        $this->app->error(function (PushApiException $e) {
            $logMessage = "Code: " . $e->getCode() . " - " . $e->getMessage();

            switch ($e->getCode()) {
                case PushApiException::NOT_FOUND:
                    self::log($logMessage, Log::WARN);
                    $this->app->response()->status(self::HTTP_NOT_FOUND);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::INVALID_DATA:
                case PushApiException::INVALID_RANGE:
                case PushApiException::INVALID_OPTION:
                case PushApiException::LIMIT_EXCEEDED:
                case PushApiException::DUPLICATED_VALUE:
                case PushApiException::ACTION_FAILED:
                    self::log($logMessage, Log::WARN);
                    $this->app->response()->status(self::HTTP_CONFLICT);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::NO_DATA:
                case PushApiException::INVALID_CALL:
                case PushApiException::INVALID_ACTION:
                    self::log($logMessage, Log::WARN);
                    $this->app->response()->status(self::HTTP_METHOD_NOT_ALLOWED);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                case PushApiException::NOT_AUTHORIZED:
                    self::log($logMessage, Log::ERROR);
                    $this->app->response()->status(self::HTTP_UNAUTHORIZED);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;

                default:
                    self::log($logMessage, Log::ERROR);
                    $this->app->response()->status(self::HTTP_INTERNAL_SERVER_ERROR);
                    $this->app->response()->header('X-Status-Reason', $e->getMessage());
                    break;
            }

            // Returns a json result for apps that does not handle HTTP responses
            $this->app->response()->header('Content-Type', 'application/json');
            $this->app->response()->body(json_encode(['result' => false]));
        });

        // If a call doesn't exist it is customized the not found
        // (default not found HTTP header) result message.
        $this->app->notFound(function () {
            self::log("Call doesn't exist on PushApi", Log::WARN);
            $this->app->response()->header('X-Status-Reason', "Call doesn't exist on PushApi");
        });
    }

    /**
     * Adds into a container all the services that the API requires.
     * @return Container The Container object fully created
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

        $c[PushApi::MAIL] = function ($c) {
            return new Mail();
        };

        $c[PushApi::ANDROID] = function ($c) {
            return new Android();
        };

        $c[PushApi::IOS] = function ($c) {
            return new Ios();
        };

        return $c;
    }

    /**
     * Sets a new parameter or service into the container storing it with an index.
     * @param string $serviceName  The reference name of the service
     * @param string $value  An instance of the service
     */
    public static function setContainerService($serviceName, $value)
    {
        self::$container[$serviceName] = $value;
    }

    /**
     * Retrieves a specific content of the container given a target index.
     * @param  string  $serviceName  The reference name of the service
     * @return Container  The content of the container
     */
    public static function getContainerService($serviceName)
    {
        return self::$container[$serviceName];
    }

    /**
     * Returns all the data that is stored into the container.
     * @return Container All the container data
     */
    public static function getContainer()
    {
        return self::$container;
    }

    /**
     * Writes the log message obtained by params in the desired level
     * @param $message  Descriptive message of the desired log
     * @param $level    Level of the log to be written (to make it more visible as higher is the level)
     */
    public static function log($message, $level = Log::EMERGENCY)
    {
        $date = date('c');
        $app = self::getContainerService(PushApi::SLIM);

        switch ($level) {
            case Log::ALERT:
                $app->log->alert("$date|\e[7;49;36mALERT\e[0m|: \e[0;49;36m$message\e[0m");
                break;
            case Log::WARN:
                $app->log->warn("$date|\e[7;49;93mWARNG\e[0m|: \e[0;49;93m$message\e[0m");
                break;
            case Log::ERROR:
                $app->log->error("$date|\e[7;49;91mERROR\e[0m|: \e[0;49;91m$message\e[0m");
                break;
            case Log::INFO:
                $app->log->debug("$date|\e[7;49;96mINFOR\e[0m|: \e[0;49;96m$message\e[0m");
                break;
            case Log::DEBUG:
                $app->log->debug("$date|\e[7;49;95mDEBUG\e[0m|: \e[0;49;95m$message\e[0m");
                break;
            default:
                $app->log->emergency("$date|\e[7;49;92mEMERG\e[0m|: \e[0;49;92m$message\e[0m");
                break;
        }
    }
}