<?php

define('DEBUG', true);

/**
 * Database configuration
 */
define('DB_USERNAME', 'db_username');
define('DB_PASSWORD', 'db_password');
define('DB_HOST', 'db_host');
define('DB_NAME', 'db_name');

/**
 * Mail configuration
 */
define('MAIL_SERVER', "mail_server");
define('MAIL_SERVER_PORT', 0);
define('MAIL_FROM', "mail@you.want");

/**
 * Redis configuration
 */
define('REDIS_IP', 'redis_ip');
define('REDIS_PORT', 0);

/**
 * Defining log routes
 */
define('PROD_LOG', "/var/tmp/production-errors.log");
define('DEV_LOG', "/var/tmp/development-errors.log");
define('PROD_SEND_LOG', "/var/tmp/production-send-messages.log");
define('DEV_SEND_LOG', "/var/tmp/development-send-messages.log");

/**
 * Defining the Android Auth Key
 */
define('ANDROID_KEY', "private_android_key");
