<?php

namespace PushApi\Config;

use PushApi\System\LogWriter;

/**
 * Database configuration
 */
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '1234567');
// luserfakerrr md5 password
// DB_PASSWORD = '0c8dc765c6373f98b4a6f8e14db60f51';
define('DB_HOST', 'localhost');
define('DB_NAME', 'push_db');

/**
 * HTTP responses
 */
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_NO_CONTENT', 204);
define('HTTP_NOT_MODIFIED', 304);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_METHOD_NOT_ACCEPTABLE', 406);
define('HTTP_CONFLICT', 409);
define('HTTP_INTERNAL_SERVER_ERROR', 500);

/**
 * Global Constants
 */
define('NO_ERRORS', 0);
define('USER_CREATE_FAILED', 1);
define('USER_ALREADY_EXISTS', 2);
define('UNKNOWN_DATABASE', 1049);

define('GOOGLE_API_KEY', "AIzaSyCHeOCzPlTlwgiqhdG3EZ_sE07FVR2OBSA");

$config = array();
$config['test'] = array(
    'mode' => 'test',
    'debug' => true,
    'log.enabled' => true,
    'log.level' => \Slim\Log::DEBUG,
    'log.writer' => new \Slim\LogWriter(fopen(__DIR__ . DIRECTORY_SEPARATOR . 'debugLog.txt', 'a'))
);

$config['dev'] = array(
    'mode' => 'development',
    'debug' => true,
    'log.enabled' => false,
);

$config['prod'] = array(
    'mode' => 'production',
    'debug' => false,
    'log.enabled' => true,
    'log.level' => \Slim\Log::WARN,
    'log.writer' => new \Slim\LogWriter(fopen(__DIR__ . DIRECTORY_SEPARATOR . 'oficialLog.txt', 'a'))
);