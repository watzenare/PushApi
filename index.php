<?php
// Runs the API and its dependencies
require 'BootStrap.php';

use \Slim\Log;
use \Slim\LogWriter;

// Start Slim Framework and PushApi
$slim = new \Slim\Slim(
    array(
        'mode' => (DEBUG ? 'development' : 'production'),
        'debug' => DEBUG,
        'log.enabled' => LOG,
        'log.level' => (DEBUG ? Log::DEBUG : Log::WARN),
        'log.writer' => new LogWriter(fopen((DEBUG ? DEV_LOG : PROD_LOG), 'a')),
    )
);
$pushApi = new \PushApi\PushApi($slim);

// Charging the API routes
require "PushApi/System/Routes.php";

$slim->run();