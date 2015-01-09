<?php

define('DEBUG', true);

/**
 * Database configuration
 */
define('DB_USERNAME', 'pushapi');
define('DB_PASSWORD', 'f69fda8c6dbbe18b');
define('DB_HOST', 'localhost');
define('DB_NAME', 'pushdb');

/**
 * Mail configuration
 */
define('MAIL_SERVER', "localhost");
define('MAIL_SERVER_PORT', 1025);
define('MAIL_FROM', "no-reply@tviso.com");

/**
 * Redis configuration
 */
define('REDIS_IP', 'localhost');
define('REDIS_PORT', '6379');

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
define('ANDROID_KEY', "AIzaSyCHeOCzPlTlwgiqhdG3EZ_sE07FVR2OBSA");