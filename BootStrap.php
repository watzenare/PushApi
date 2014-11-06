<?php
// Includes Slim Framework and vendor libraries
require 'vendor/autoload.php';

// Include configurations and global PushApi constants
require "PushApi/Config/Config.php";

// Include the database configuration
require "PushApi/Config/database.php";

// Includes all files of the PushApi project given it's namespaces and transforms it into directories and files
function autoloader($class) {
    $namespace = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    include __DIR__ . DIRECTORY_SEPARATOR . $namespace . '.php';
}
spl_autoload_register('autoloader');

// Starts PushApi
$pushApi = new \PushApi\PushApi($config['dev']);