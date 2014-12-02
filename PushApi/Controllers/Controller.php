<?php

namespace PushApi\Controllers;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the global actions that all controllers can do.
 */
class Controller
{
    protected $slim;
    protected $redis;

    /**
     * Main constructor that catches an instance of the framework
     */
    public function __construct() {
        $this->slim = \Slim\Slim::getInstance();
        $this->redis = new \Credis_Client('localhost');
    }

    /**
     * Prepares an HTML response, it modifies some response headers and prepares the
     * resulting data encoding it into json and sends it to the client 
     * @param  [array] $result Array with resulting values
     */
    protected function send($result)
    {
        $response = $this->slim->response();
        $response->header('Content-Type', JSON);
        $response->body(json_encode(array('result' => $result)));
        $this->slim->stop();
    }

    /**
     * Deletes unset parameters given an array
     * @param  array $data
     * @return array       An updated array without unset params
     */
    protected function cleanParams($data = array()) {
        foreach ($data as $key => $value) {
            if (is_null($data[$key])) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Changes the value of an integer changing it into a boolean value
     * @param  [int] $data Integer
     * @return [bool]       Boolean value
     */
    protected function boolinize($data) {
        return ((int)$data != 0);
    }
}