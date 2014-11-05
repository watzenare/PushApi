<?php

namespace PushApi\Controllers;

use \PushApi\Controllers\Dao;
use \PushApi\PushApiException;


class Auth extends Dao
{
    const TABLE_NAME = 'auth';

    // Only can be created this number of apps
    const MAX_APPS_ENABLED = 1;

    protected $fields = array();
}