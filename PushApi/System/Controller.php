<?php

namespace PushApi\System;

use \PushApi\System\Medoo;


class Controller
{
	protected $dbLink;

	public function __construct() {
		$this->dbLink = new Medoo();
	}
}
