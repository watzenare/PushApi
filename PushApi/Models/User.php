<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;
use \PushApi\Models\Subscribed;

class User extends Eloquent
{
    public $timestamps = false;
    protected $fillable = array('email');
    protected $guarded = array('id','created');
    protected $hidden = array('created');

    public function subscriptions()
    {
        return $this->hasMany('\PushApi\Models\Subscribed');
    }
}