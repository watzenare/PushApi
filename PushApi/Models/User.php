<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

class User extends Eloquent
{
    public $timestamps = false;
    protected $fillable = array('email');
    protected $guarded = array('id','created');
    protected $hidden = array('created');

    public function channels()
    {
        return $this->belongsToMany('Channel', 'Subscribed');
    }
}