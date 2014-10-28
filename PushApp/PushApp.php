<?php

namespace PushApp;

use \Slim\Slim;
use \PushApp\System\Db;
use \PushApp\System\Util;
use \PushApp\System\LogWriter;

class PushApp
{
	private $appName;
    private $slim;
	private $db;

	public function __construct($name) {

		$this->setName($name);

		require "Config/Config.php";

        $this->setSlim(new Slim($config['test']));

        $this->db = new Db($this->slim);

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
}