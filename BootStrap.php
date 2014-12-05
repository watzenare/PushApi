<?php
// Includes vendor libraries
require "vendor/autoload.php";

// Include configurations and global PushApi constants
require "PushApi/System/config.php";
require "PushApi/System/headers.php";
require	"PushApi/System/database.php";

// Includes PushApi files using its namespaces
function autoloader($class) {
    $namespace = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    include __DIR__ . DIRECTORY_SEPARATOR . $namespace . '.php';
}
spl_autoload_register('autoloader');

// Start Slim Framework and PushApi
$slim = new \Slim\Slim(array('mode' => 'development'));
$pushApi = new \PushApi\PushApi($slim);

// Charging the API routes
require "PushApi/System/Routes.php";

$slim->run();