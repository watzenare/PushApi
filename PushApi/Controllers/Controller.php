<?php

namespace PushApi\Controllers;


class Controller
{
    protected $slim;

    public function __construct($slim) {
        $this->slim = $slim;
    }

    protected function send($result)
    {
        $response = $this->slim->response();

        $response->header('Content-Type', JSON);

        $response->body(json_encode(array('result' => $result)));
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
     * [boolinize description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    protected function boolinize($data) {
        return ((int)$data != 0);
    }
}