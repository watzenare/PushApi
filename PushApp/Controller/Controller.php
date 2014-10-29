<?php

namespace PushApp\Controller;

use \PushApp\System\Medoo;


class Controller
{
	protected $dbLink;

	public function __construct() {
		$this->dbLink = new Medoo();
	}
}
