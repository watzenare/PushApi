<?php

// Includes Slim Framework and vendor libraries
require 'vendor/autoload.php';

// Includes all files of the project and it lets the application runs
function autoloader($class) {
    // Changes namespaces to real directories by updating the direction of slashes
    $namespace = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    include __DIR__ . DIRECTORY_SEPARATOR . $namespace . '.php';
}
spl_autoload_register('autoloader');

// Starts the Push App
$pushApp = new \PushApp\PushApp('Test');