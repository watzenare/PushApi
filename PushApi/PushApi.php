<?php

namespace PushApi;

use \Slim\Slim;
use \PushApi\System\Util;
use \PushApi\System\LogWriter;

class PushApi
{
	private $appName;
    private $slim;

	public function __construct($name) {

		$this->setName($name);

		require "Config/Config.php";

        $this->setSlim(new Slim($config['dev']));

        require "System/Routes.php";

		$this->slim->run();
    }

    public function setName($name) {
    	$this->appName = $name;
    }

    public function getName() {
    	return $this->appName;
    }

    public function setSlim($slim) {
    	$this->slim = $slim;
    }

    public function getSlim() {
    	return $this->slim;
    }

    public function sendResponse($result, $error) {

        $this->slim->response()->header('Content-Type', 'application/json');

        $response = json_encode(array(
            "result" => $result,
            "error" => $error
        ));
        $this->slim->response()->body($response);
    }
}