<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

class Channel extends Eloquent
{
	public $timestamps = false;
    protected $hidden = array('created');

    public function subscriptions()
    {
        return $this->hasMany('\PushApi\Models\Subscribed');
    }
}