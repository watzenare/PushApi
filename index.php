<?php
// Runs the API and its dependencies
require 'BootStrap.php';

// Start Slim Framework and PushApi
$slim = new \Slim\Slim(
    array(
        'mode' => (DEBUG ? 'development' : 'production'),
        'debug' => DEBUG
    )
);
$pushApi = new \PushApi\PushApi($slim);

// Charging the API routes
require "PushApi/System/Routes.php";

$slim->run();