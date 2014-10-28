<?php

namespace PushApp\System;

use \PushApp\Config\Config;

/**
 * Handling database connection
 */
class DbConnect
{

    private $con;

    // constructor
    function __construct() {
  
    }
  
    // destructor
    function __destruct() {
        // $this->close();
    }

    /**
     * Establishing database connection
     * @return database connection handler
     */
    function connect() {

        // Connecting to mysql database
        $this->con = new mysqli(Config::DB_HOST, Config::DB_USERNAME, Config::DB_PASSWORD, Config::DB_NAME);

        // Check for database connection error
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }

        // returing connection resource
        return $this->con;
    }

    // Closing database connection
    public function close() {
        mysql_close();
    }
}