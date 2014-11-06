<?php

namespace PushApi\Models;

use \PushApi\Controllers\Dao;
use \PushApi\PushApiException;


class Auth extends Dao
{
    protected $table = 'my_users';
    public $timestamps = false;

    // Only can be created MAX num of apps
    const MAX_APPS_ENABLED = 1;
}