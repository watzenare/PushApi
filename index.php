<?php
// Runs the API and its dependencies
require 'BootStrap.php';

// Start Slim Framework and PushApi
$slim = new \Slim\Slim(array('mode' => 'production'));
$pushApi = new \PushApi\PushApi($slim);

// Charging the API routes
require "PushApi/System/Routes.php";

$slim->run();