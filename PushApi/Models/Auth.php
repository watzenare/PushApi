<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

class Auth extends Eloquent
{
    protected $table = 'auth';
    public $timestamps = false;

    // Only can be created MAX num of apps
    const MAX_APPS_ENABLED = 1;
}