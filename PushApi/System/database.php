<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher as Dispatcher;

// Instantiates an Eloquent helper
$capsule = new Capsule;

$capsule->addConnection(array(
	'driver' => 'mysql',
	'host' => DB_HOST,
	'database' => DB_NAME,
	'username' => DB_USERNAME,
	'password' => DB_PASSWORD,
	'collation' => 'utf8_general_ci',
	'charset' => 'utf8',
));

$capsule->setEventDispatcher(new Dispatcher);

$capsule->bootEloquent();