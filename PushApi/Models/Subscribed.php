<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

class Subscribed extends Eloquent
{
	protected $table = 'subscribed';
    public $timestamps = false;
    protected $hidden = array('created');
}